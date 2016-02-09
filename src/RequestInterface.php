<?php
/**
 * Created by PhpStorm.
 * User: lejla
 * Date: 2016.02.09.
 * Time: 17:34
 */

namespace CurlX;

/**
 * Interface RequestInterface
 * @package CurlX
 *
 * @property string $url
 * @property array $post
 * @property float $time
 * @property int $timeout
 * @property array $options
 * @property array $headers
 * @property resource $handle
 * @property callable[] $listeners
 * @property mixed $response
 */
interface RequestInterface
{
    function getUrl();

    function setUrl($url);

    function getPostData();

    function setPostData(array $postData);

    function getTime();

    function startTimer();

    function stopTimer();

    function getResult();

    /**
     * This gets called when a request has completed
     * @param $result
     */
    function callBack($result);

    /**
     * Add a listener that gets notified when the Request has completed
     * @param callable $function
     * @return mixed
     */
    function addListener(callable $function);

    function setTimeout($mSecs);

    function getHandle();

    function setHeaders(array $headers);

    function getHeaders();

    function setOptions(array $options);

    function getOptions();
}