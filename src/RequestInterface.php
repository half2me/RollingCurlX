<?php

namespace CurlX;

/**
 * Interface RequestInterface
 * @package CurlX
 *
 * @property string $url url of the Request
 * @property array $post array of post data
 * @property float $time running time of the request
 * @property int $timeout time (in msec) after which the request will be aborted
 * @property array $options cUrl options of the request
 * @property array $headers headers of the request
 * @property resource $handle cUrl handle of the request
 * @property callable[] $listeners array of registered listeners which will be called upon when request finishes
 * @property mixed $response curl's response
 */
interface RequestInterface
{
    /**
     * Getter for url field
     * @return string url
     */
    public function getUrl();

    /**
     * Setter for the url field
     * @param string $url url
     * @return void
     */
    public function setUrl($url);

    /**
     * Getter for the post data array
     * @return array post data
     */
    public function getPostData();

    /**
     * Setter for the post data array
     * @param array $postData post data
     * @return void
     */
    public function setPostData(array $postData);

    /**
     * Returns the time (msec) it took to make the request
     * @return float time
     */
    public function getTime();

    /**
     * Start the request's internal timer
     * @return void
     */
    public function startTimer();

    /**
     * Stops the request's internal timer
     * @return void
     */
    public function stopTimer();

    /**
     * Get the result of a query
     * @return mixed result
     */
    public function getResult();

    /**
     * This gets called by an agent when a request has completed
     * @param mixed $result result
     * @return void
     */
    public function callBack($result);

    /**
     * Add a listener that gets notified when the Request has completed
     * @param callable $function callback function
     * @return void
     */
    public function addListener(callable $function);

    /**
     * Set a timeout value for the request
     * @param float $timeout timeout (msec)
     * @return void
     */
    public function setTimeout($timeout);

    /**
     * Get the timeout value registered for the request
     * @return float timeout
     */
    public function getTimeout();

    /**
     * Get the cUrl handle for the request
     * @return resource cUrl handle
     */
    public function getHandle();

    /**
     * Add headers to the request
     * @param array $headers headers in ['key' => 'value] format
     * @return void
     */
    public function setHeaders(array $headers);

    /**
     * Get headers set for the request
     * @return array headers in ['key' => 'value'] format
     */
    public function getHeaders();

    /**
     * Add cUrl options to the request
     * @param array $options options in ['key' => 'value'] format
     * @return void
     */
    public function setOptions(array $options);

    /**
     * Get cUrl options set for the request
     * @return array options in ['key' => 'value'] format
     */
    public function getOptions();

    /**
     * Get the response for the finished query
     * @return mixed response
     */
    public function getResponse();
}
