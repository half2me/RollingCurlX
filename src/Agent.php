<?php

namespace CurlX;

class Agent
{
    protected $maxConcurrent = 0; //max. number of simultaneous connections allowed
    protected $options = []; //shared cURL options
    protected $headers = []; //shared cURL request headers
    protected $timeout = 5000; //timeout used for curl_multi_select function
    protected $post = [];
    protected $startTime;
    protected $endTime;

    /**
     * @var RequestInterface[] $requests array of Requests
     */
    protected $requests;

    /**
     * @var callable[] $listeners array of listeners
     */
    protected $listeners = [];
    protected $mh;

    /**
     * Agent constructor.
     * @param int $max_concurrent max current requests
     */
    function __construct($max_concurrent = 10)
    {
        $this->setMaxConcurrent($max_concurrent);
    }

    /**
     * Set the maximum number of concurrent requests
     * @param int $max_requests maximum concurrent requests
     */
    public function setMaxConcurrent($max_requests)
    {
        if ($max_requests > 0) {
            $this->maxConcurrent = $max_requests;
        }
    }

    /**
     * Set global cUrl options
     * @param array $options array of options
     */
    public function setOptions(array $options)
    {
        if(!empty($options)) {
            $this->options += $options;
        }
    }

    /**
     * Set global cUrl headers
     * @param array $headers headers
     */
    public function setHeaders(array $headers)
    {
        if (!empty($headers)) {
            $this->headers += $headers;
        }
    }

    /**
     * Set global timeout
     * If individual requests don't have a timeout value, this will be used
     * @param int $timeout timeout in msec
     */
    public function setTimeout($timeout)
    {
        if ($timeout > 0) {
            $this->timeout = $timeout; // to seconds
        }
    }

    /**
     * Adds a new request to the queue and returns it
     * this request will have its default options set to global options
     * @return RequestInterface the newly added request object
     */
    public function request()
    {
        return $this->addRequest(new Request(), true);
    }

    /**
     * Add a request to the request queue
     * @param RequestInterface $request the request to add
     * @return RequestInterface
     */
    public function addRequest(RequestInterface $request, $setGlobals = false)
    {
        $this->requests = $request;
        if($setGlobals) {
            if(!empty($this->post)) $request->post = $this->post;
            if(!empty($this->headers)) $request->headers = $this->headers;
            if(!empty($this->options)) $request->options = $this->options;
            $request->timeout = $this->timeout;
        }
        return $request;
    }

    /**
     * Returns the Request object for a give cUrl handle
     * @param mixed $handle
     * @return RequestInterface request with handle
     */
    private function getRequestByHandle($handle)
    {
        foreach($this->requests as $request) {
            if($request->handle === $handle) {
                return $request;
            }
        }
    }

    /**
     * Execute the request queue
     */
    public function execute()
    {
        $this->mh = curl_multi_init();

        foreach($this->requests as $key => $request) {
            curl_multi_add_handle($this->mh, $request->handle);
            $request->startTimer();
            if($key >= $this->maxConcurrent) break;
        }

        do{
            do{
                $mh_status = curl_multi_exec($this->mh, $active);
            } while($mh_status == CURLM_CALL_MULTI_PERFORM);
            if($mh_status != CURLM_OK) {
                break;
            }

            // a request just completed, find out which one
            while($completed = curl_multi_info_read($this->mh)) {
                $request = $this->getRequestByHandle($completed['handle']);
                $request->callback($completed);

                //add/start a new request to the request queue
            }

            usleep(15);
        } while ($active);
        curl_multi_close($this->mh);
    }


    private function process_request($completed, $multi_handle, array &$requests_map) {
        $ch = $completed['handle'];
        $ch_hash = (string) $ch;
        $request =& $this->requests[$requests_map[$ch_hash]]; //map handler to request index to get request info

        $request_info = curl_getinfo($ch);
        $request_info['curle'] = $completed['result'];
        $request_info['curle_msg'] = $this->curle_msgs[$completed['result']];
        $request_info['handle'] = $ch;
        $request_info['time'] = $time = $this->stopTimer($request); //record request time
        $request_info['url_raw'] = $url = $request['url'];
        $request_info['user_data'] = $user_data = $request['user_data'];

        if(curl_errno($ch) !== 0 || intval($request_info['http_code']) !== 200) { //if server responded with http error
            $response = false;
        } else { //sucessful response
            $response = curl_multi_getcontent($ch);
        }

        //get request info
        $callback = $request['callback'];
        $options = $request['options'];

        if($response && (isset($this->_options[CURLOPT_HEADER]) || isset($options[CURLOPT_HEADER]))) {
            $k = intval($request_info['header_size']);
            $request_info['response_header'] = substr($response, 0, $k);
            $response = substr($response, $k);
        }

        //remove completed request and its curl handle
        unset($requests_map[$ch_hash]);
        curl_multi_remove_handle($multi_handle, $ch);

        //call the callback function and pass request info and user data to it
        if($callback) {
            call_user_func($callback, $response, $url, $request_info, $user_data, $time);
        }
        $request = NULL; //free up memory now just incase response was large
    }


    private function check_for_timeouts($mh) {
        $now = microtime(true);
        $request_maps = $this->_request_map;
        $requests = $this->_request_map;
        foreach($request_maps as $ch_hash => $request_num) {
            $request = $requests[$request_num];
            $timeout = $request->timeout;
            $start_time = $request->start_time;
            $ch = $request->handle;
            if($now >=  $start_time + $timeout) {
                curl_multi_remove_handle($mh, $ch);
            }
        }
    }

    private $curle_msgs = [CURLE_OK => 'OK', CURLE_UNSUPPORTED_PROTOCOL => 'UNSUPPORTED_PROTOCOL', CURLE_FAILED_INIT => 'FAILED_INIT', CURLE_URL_MALFORMAT => 'URL_MALFORMAT', CURLE_URL_MALFORMAT_USER => 'URL_MALFORMAT_USER', CURLE_COULDNT_RESOLVE_PROXY => 'COULDNT_RESOLVE_PROXY', CURLE_COULDNT_RESOLVE_HOST => 'COULDNT_RESOLVE_HOST', CURLE_COULDNT_CONNECT => 'COULDNT_CONNECT', CURLE_FTP_WEIRD_SERVER_REPLY => 'FTP_WEIRD_SERVER_REPLY', CURLE_FTP_ACCESS_DENIED => 'FTP_ACCESS_DENIED', CURLE_FTP_USER_PASSWORD_INCORRECT => 'FTP_USER_PASSWORD_INCORRECT', CURLE_FTP_WEIRD_PASS_REPLY => 'FTP_WEIRD_PASS_REPLY', CURLE_FTP_WEIRD_USER_REPLY => 'FTP_WEIRD_USER_REPLY', CURLE_FTP_WEIRD_PASV_REPLY => 'FTP_WEIRD_PASV_REPLY', CURLE_FTP_WEIRD_227_FORMAT => 'FTP_WEIRD_227_FORMAT', CURLE_FTP_CANT_GET_HOST => 'FTP_CANT_GET_HOST', CURLE_FTP_CANT_RECONNECT => 'FTP_CANT_RECONNECT', CURLE_FTP_COULDNT_SET_BINARY => 'FTP_COULDNT_SET_BINARY', CURLE_PARTIAL_FILE => 'PARTIAL_FILE', CURLE_FTP_COULDNT_RETR_FILE => 'FTP_COULDNT_RETR_FILE', CURLE_FTP_WRITE_ERROR => 'FTP_WRITE_ERROR', CURLE_FTP_QUOTE_ERROR => 'FTP_QUOTE_ERROR', CURLE_HTTP_NOT_FOUND => 'HTTP_NOT_FOUND', CURLE_WRITE_ERROR => 'WRITE_ERROR', CURLE_MALFORMAT_USER => 'MALFORMAT_USER', CURLE_FTP_COULDNT_STOR_FILE => 'FTP_COULDNT_STOR_FILE', CURLE_READ_ERROR => 'READ_ERROR', CURLE_OUT_OF_MEMORY => 'OUT_OF_MEMORY', CURLE_OPERATION_TIMEOUTED => 'OPERATION_TIMEOUTED', CURLE_FTP_COULDNT_SET_ASCII => 'FTP_COULDNT_SET_ASCII', CURLE_FTP_PORT_FAILED => 'FTP_PORT_FAILED', CURLE_FTP_COULDNT_USE_REST => 'FTP_COULDNT_USE_REST', CURLE_FTP_COULDNT_GET_SIZE => 'FTP_COULDNT_GET_SIZE', CURLE_HTTP_RANGE_ERROR => 'HTTP_RANGE_ERROR', CURLE_HTTP_POST_ERROR => 'HTTP_POST_ERROR', CURLE_SSL_CONNECT_ERROR => 'SSL_CONNECT_ERROR', CURLE_FTP_BAD_DOWNLOAD_RESUME => 'FTP_BAD_DOWNLOAD_RESUME', CURLE_FILE_COULDNT_READ_FILE => 'FILE_COULDNT_READ_FILE', CURLE_LDAP_CANNOT_BIND => 'LDAP_CANNOT_BIND', CURLE_LDAP_SEARCH_FAILED => 'LDAP_SEARCH_FAILED', CURLE_LIBRARY_NOT_FOUND => 'LIBRARY_NOT_FOUND', CURLE_FUNCTION_NOT_FOUND => 'FUNCTION_NOT_FOUND', CURLE_ABORTED_BY_CALLBACK => 'ABORTED_BY_CALLBACK', CURLE_BAD_FUNCTION_ARGUMENT => 'BAD_FUNCTION_ARGUMENT', CURLE_BAD_CALLING_ORDER => 'BAD_CALLING_ORDER', CURLE_HTTP_PORT_FAILED => 'HTTP_PORT_FAILED', CURLE_BAD_PASSWORD_ENTERED => 'BAD_PASSWORD_ENTERED', CURLE_TOO_MANY_REDIRECTS => 'TOO_MANY_REDIRECTS', CURLE_UNKNOWN_TELNET_OPTION => 'UNKNOWN_TELNET_OPTION', CURLE_TELNET_OPTION_SYNTAX => 'TELNET_OPTION_SYNTAX', CURLE_OBSOLETE => 'OBSOLETE', CURLE_SSL_PEER_CERTIFICATE => 'SSL_PEER_CERTIFICATE', CURLE_GOT_NOTHING => 'GOT_NOTHING', CURLE_SSL_ENGINE_NOTFOUND => 'SSL_ENGINE_NOTFOUND', CURLE_SSL_ENGINE_SETFAILED => 'SSL_ENGINE_SETFAILED', CURLE_SEND_ERROR => 'SEND_ERROR', CURLE_RECV_ERROR => 'RECV_ERROR', CURLE_SHARE_IN_USE => 'SHARE_IN_USE', CURLE_SSL_CERTPROBLEM => 'SSL_CERTPROBLEM', CURLE_SSL_CIPHER => 'SSL_CIPHER', CURLE_SSL_CACERT => 'SSL_CACERT', CURLE_BAD_CONTENT_ENCODING => 'BAD_CONTENT_ENCODING', CURLE_LDAP_INVALID_URL => 'LDAP_INVALID_URL', CURLE_FILESIZE_EXCEEDED => 'FILESIZE_EXCEEDED', CURLE_FTP_SSL_FAILED => 'FTP_SSL_FAILED', CURLE_SSH => 'SSH'
    ];
}
?>