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

    // TODO: normalize headers key => value is key: value

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
     * @param null $url URL to send the request to
     * @return RequestInterface the newly added request object
     */
    public function newRequest($url = null)
    {
        return $this->addRequest(new Request($url), true);
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
                curl_multi_remove_handle($this->mh, $completed['handle']);

                // TODO: Add the next request to the queue
            }

            usleep(15);
        } while ($active);
        curl_multi_close($this->mh);
    }
}
?>