<?php
/**
 * Created by PhpStorm.
 * User: lejla
 * Date: 2016.02.09.
 * Time: 17:34
 */

namespace CurlX;


interface RequestInterface
{
    function getUrl();

    function setUrl($url);

    function getPostData();

    function setPostData(array $postData);

    function getTime();

    function startTimer();

    function getResult();

    function callBack($result);

    function addListener(callable $function);

    function setTimeout($mSecs);

    function getHandle();

    function setHeaders(array $headers);

    function getHeaders();

    function setOptions(array $options);

    function getOptions();
}