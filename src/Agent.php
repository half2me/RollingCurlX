<?php

namespace CurlX;

/**
 * Class Agent
 * @package CurlX
 *
 * @property int $max_concurrent The maximum number of simultaneous connections allowed
 * @property int $maxConcurrent The maximum number of simultaneous connections allowed
 * @property string $url default url for requests
 * @property array $post array of default post data for requests
 * @property float $time running time of the agent
 * @property int $timeout default timeout (in msec) for requests
 * @property array $options default cUrl options for requests
 * @property array $headers default headers for requests
 * @property resource $handle cUrl Multi Handle
 * @property callable[] $listeners array of registered listeners which will be registered to newly created requests
 * @property array $response responses of the individual requests
 */
class Agent
{
    /**
     * @var array results
     */
    protected $result;

    /**
     * @var array responses
     */
    protected $response;

    /**
     * @var int The maximum number of simultaneous connections allowed
     */
    protected $maxConcurrent = 0;

    /**
     * @var RequestInterface[] array of Requests
     */
    protected $requests;

    /**
     * @var Request default request
     */
    protected $defaultRequest;

    /**
     * @var resource cUrl Multi Handle
     */
    protected $mh;

    protected $requestCounter = 0;

    /**
     * Agent constructor.
     * @param int $max_concurrent max current requests
     */
    function __construct($max_concurrent = 10)
    {
        $this->setMaxConcurrent($max_concurrent);
        $this->defaultRequest = new Request();
        $this->mh = curl_multi_init();
    }

    function __destruct()
    {
        foreach($this->requests as $request) {
            curl_multi_remove_handle($this->mh, $request->handle);
        }
        curl_multi_close($this->mh);
    }

    /**
     * Magic setter function
     * @param string $name attribute to set
     * @param mixed $value the new value
     * @return void
     */
    public function __set($name, $value)
    {
        $c = Request::camelize($name);
        $m = "set$c";
        if (method_exists($this, $m)) {
            $this->$m($value);
        } else {
            $this->defaultRequest->__set($name, $value);
        }
    }

    /**
     * Magic getter function
     * @param string $name of the attribute to get
     * @return mixed the attribute's value
     */
    public function __get($name)
    {
        $c = Request::camelize($name);
        $m = "get$c";
        if (method_exists($this, $m)) {
            return $this->$m();
        } else {
            return $this->defaultRequest->__get($name);
        }
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
     * Get the currently set value of maxConcurrent
     * @return int maximum number of concurrent requests
     */
    public function getMaxConcurrent()
    {
        return $this->maxConcurrent;
    }

    /**
     * Adds a new request to the queue and returns it
     * this request will have its default options set to global options
     * @param null $url URL to send the request to
     * @return RequestInterface the newly added request object
     */
    public function newRequest($url = null)
    {
        $request = clone $this->defaultRequest;
        $request->url = $url;
        return $this->addRequest($request);
    }

    /**
     * Add a request to the request queue
     * @param RequestInterface $request the request to add
     * @return RequestInterface
     */
    public function addRequest(RequestInterface $request)
    {
        $this->requests[] = $request;
        return $request;
    }

    /**
     * Returns the Request object for a give cUrl handle
     * @param resource $handle cUrl handle
     * @return RequestInterface Request with handle
     */
    protected function getRequestByHandle($handle)
    {
        foreach ($this->requests as $request) {
            if ($request->handle === $handle) {
                return $request;
            }
        }
    }

    /**
     * Execute the request queue
     */
    public function execute()
    {
        foreach ($this->requests as $key => $request) {
            if ($this->requestCounter >= $this->maxConcurrent) {
                break;
            }
            curl_multi_add_handle($this->mh, $request->handle);
            $this->requestCounter++;
        }

        // Start the request
        do {
            $mrc = curl_multi_exec($this->mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            while (curl_multi_exec($this->mh, $active) === CURLM_CALL_MULTI_PERFORM) ;

            if (curl_multi_select($this->mh) != -1) {
                do {
                    $mrc = curl_multi_exec($this->mh, $active);
                    if ($mrc == CURLM_OK) {
                        while ($info = curl_multi_info_read($this->mh)) {
                            $this->getRequestByHandle($info['handle'])->callBack($info);
                        }
                    }
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }
    }

    public function addListener(callable $function)
    {
        $this->defaultRequest->addListener($function);
    }
}
