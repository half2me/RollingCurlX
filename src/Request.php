<?php
/**
 * Created by PhpStorm.
 * User: lejla
 * Date: 2016.02.09.
 * Time: 17:34
 */

namespace CurlX;

/**
 * Class Request
 * @package CurlX
 *
 * @property string $url
 * @property array $post
 * @property float $time
 * @property int $timeout
 * @property array $options
 * @property array $headers
 * @property resource $handle
 * @property mixed $response
 * @property callable[] $listeners
 */
class Request implements RequestInterface
{
    protected $url;
    protected $post = [];
    protected $startTime;
    protected $endTime;
    protected $result;
    protected $listeners = [];
    protected $timeout;
    protected $curlHandle;
    protected $headers = [];
    protected $options = [];
    protected $success;
    protected $response;

    private function camelize($str)
    {
        return str_replace('_', '', ucwords($str, '_'));
    }

    public function __set($name, $value)
    {
        $c = $this->camelize($name);
        $m = "set$c";
        if (method_exists($this, $m)) {
            return $this->$m($value);
        } else {
            user_error("undefined property $name");
        }
    }

    public function __get($name)
    {
        $c = $this->camelize($name);
        $m = "get$c";
        if (method_exists($this, $m)) {
            return $this->$m();
        } else {
            user_error("undefined property $name");
        }
    }

    /**
     * Request constructor.
     * @param string $url optional url
     */
    public function __construct($url = null)
    {
        $this->setUrl($url);

        // Defaults
        $this->options[CURLOPT_RETURNTRANSFER] = true;
        $this->options[CURLOPT_NOSIGNAL] = 1;
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
        $this->options[CURLOPT_POST] = 1;
        if (!empty($this->post)) {
            $this->options[CURLOPT_POSTFIELDS] = http_build_query($this->post);
        }
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
        $this->startTime = microtime(true);
    }

    public function stopTimer()
    {
        $this->endTime = microtime(true);
    }

    public function getResult()
    {
        return $this->result;
    }

    public function callBack($result)
    {
        $this->stopTimer();
        $this->result = $result;

        $requestInfo = curl_getinfo($this->curlHandle);

        if (curl_errno($this->curlHandle) !== 0 || intval($requestInfo['http_code']) !== 200) {
            $this->success = false;
        } else {
            $this->success = true;
            $this->response = curl_multi_getcontent($this->ch);
        }

        $this->notify();
    }

    public function addListener(callable $function)
    {
        if (is_callable($function)) {
            $this->listeners += $function;
        }
    }

    protected function notify()
    {
        foreach ($this->listeners as $listener) {
            call_user_func($listener, $this);
        }
    }

    public function setTimeout($timeout)
    {
        if ($timeout > 0) {
            $this->timeout = $timeout;
            $this->options[CURLOPT_TIMEOUT_MS] = $this->timeout;
        }
    }

    public function getHandle()
    {
        if (!isset($this->curlHandle)) {
            $this->curlHandle = curl_init($this->url);
            curl_setopt_array($this->curlHandle, $this->options);
        }

        return $this->curlHandle;
    }

    function setHeaders(array $headers)
    {
        $this->headers += $headers;
        $this->options[CURLOPT_HTTPHEADER] = $headers;
    }

    function getHeaders()
    {
        return $this->headers;
    }

    function setOptions(array $options)
    {
        $this->options += $options;
    }

    function getOptions()
    {
        return $this->options;
    }

    function getResponse()
    {
        return $this->response;
    }
}
