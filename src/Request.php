<?php
/**
 * Created by PhpStorm.
 * User: lejla
 * Date: 2016.02.09.
 * Time: 17:34
 */

namespace CurlX;


class Request implements RequestInterface
{
    protected $url;
    protected $post = [];
    protected $startTime;
    protected $endTime;
    protected $result;
    protected $listeners = [];
    protected $timeout;

    private function camelize($str)
    {
        return str_replace('_', '', ucwords($str, '_'));
    }

    public function __set($name, $value)
    {
        $c = $this->camelize($name);
        $m = "set$c";
        if(method_exists($this, $m)) {
            return $this->$m($value);
        }
        else user_error("undefined property $name");
    }

    public function __get($name) {
        $c = $this->camelize($name);
        $m = "get$c";
        if(method_exists($this, $m)) {
            return $this->$m();
        }
        else user_error("undefined property $name");
    }

    public function setUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
            $this->url = $url;
        }
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setPostData(array $postValues)
    {
        $this->post += $postValues;
    }

    public function getPostData()
    {
        return $this->post;
    }

    public function getTime()
    {
        return $this->endTime - $this->startTime;
    }

    public function startTimer()
    {

    }

    private function stopTimer()
    {

    }

    public function getResult()
    {
        return $this->result;
    }

    public function callBack($result)
    {
        $this->stopTimer();
        $this->result = $result;
    }

    public function addListener(callable $function)
    {
        if(is_callable($function)) {
            $this->listeners += $function;
        }
    }

    protected function notify()
    {
        foreach($this->listeners as $listener)
        {
           call_user_func($listener);
        }
    }

    public function setTimeout($timeout)
    {
        if ($timeout > 0) {
            $this->timeout = $timeout;
        }
    }
}