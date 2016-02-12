<?php

namespace CurlX\Tests;

use CurlX\Agent;
use CurlX\RequestInterface;
use PHPUnit_Framework_TestCase;

/**
 * Class AgentTest
 * @package CurlX\Tests
 *
 * @property Agent $agent
 * @property string $localTestUrl
 */
class AgentTest extends PHPUnit_Framework_TestCase
{
    protected $agent;
    protected $localTestUrl;

    public function setUp()
    {
        $this->agent = new Agent(20);
        $this->localTestUrl = 'http://localhost:8000/echo.php';
    }

    public function testNewRequest()
    {
        // We set the default parameters
        $this->agent->url = $this->localTestUrl;
        $this->agent->post_data = ['a' => 'a'];
        $this->agent->headers = ['a' => 'a'];
        $this->agent->timeout = 5;
        $this->agent->options = [CURLOPT_BINARYTRANSFER => true];
        $this->agent->addListener(function (RequestInterface $r) {
        });

        $r = $this->agent->newRequest();
        $this->assertInstanceOf('CurlX\RequestInterface', $r);

        // Check that they were transferred properly to the newly created Request
        $this->assertEquals($this->agent->url, $r->url);
        $this->assertEquals($this->agent->post_data, $r->post_data);
        $this->assertEquals($this->agent->headers, $r->headers);
        $this->assertEquals($this->agent->timeout, $r->timeout);
        $this->assertEquals($this->agent->options, $r->options);
    }

    public function testExecute()
    {
        $called = 0;

        $this->agent->addListener(function (RequestInterface $req) use (&$called) {
            $this->assertInstanceOf('CurlX\RequestInterface', $req);
            $called++;
        });

        $r = [];
        $this->agent->url = $this->localTestUrl;

        for ($i = 0; $i < 20; $i++) {
            $r[] = $this->agent->newRequest();
        }
        $this->assertEquals($this->agent->url, $r[0]->url);

        $this->agent->execute();

        $this->assertEquals(20, $called);

        foreach ($r as $key => $req) {
            $this->assertNotNull($req->response);
            $this->assertJson($req->response);
            $this->assertArrayHasKey('server', json_decode($req->response, true));
        }
    }
}
