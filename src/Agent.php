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
     * @param int $maxConcurrent max current requests
     */
    public function __construct($maxConcurrent = 10)
    {
        $this->setMaxConcurrent($maxConcurrent);
        $this->defaultRequest = new Request();
        $this->mh = curl_multi_init();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
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
     * Add a default listener to be added to all new Requests
     * @param callable $listener the listener
     * @return void
     */
    public function addListener(callable $listener)
    {
        $this->defaultRequest->addListener($listener);
    }

    /**
     * Set the maximum number of concurrent requests
     * @param int $maxConcurrent maximum concurrent requests
     * @return void
     */
    public function setMaxConcurrent($maxConcurrent)
    {
        if ($maxConcurrent > 0) {
            $this->maxConcurrent = $maxConcurrent;
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
     * @return void
     */
    public function execute()
    {
        // start the first batch of requests
        $numOfRequests = count($this->requests);
        while ($this->requestCounter < $this->maxConcurrent && $this->requestCounter < $numOfRequests) {
            curl_multi_add_handle($this->mh, $this->requests[$this->requestCounter]->handle);
            $this->requestCounter++;
        }

        do {
            while (($mrc = curl_multi_exec($this->mh, $running)) == CURLM_CALL_MULTI_PERFORM) {

            }
            if ($mrc != CURLM_OK) {
                break;
            }

            // a request was just completed -- find out which one
            while ($done = curl_multi_info_read($this->mh)) {
                // Callback
                $this->getRequestByHandle($done['handle'])->callBack($done);

                // start a new request
                if ($this->requestCounter < $numOfRequests) {
                    curl_multi_add_handle($this->mh, $this->requests[$this->requestCounter]->handle);
                    $this->requestCounter++;

                    // remove the curl handle that just completed
                    curl_multi_remove_handle($this->mh, $done['handle']);
                }
            }
        } while ($running);
    }
}
