<?php

namespace CurlX\Tests;

use CurlX\Agent;
use CurlX\RequestInterface;
use PHPUnit_Framework_TestCase;

class AgentTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    public function tearDown()
    {

    }

    public function testSomething()
    {
        $agent = new Agent(10);

        $called = 0;

        $agent->addListener(function(RequestInterface $req) use (&$called) {
            $this->assertInstanceOf('CurlX\RequestInterface', $req);
            $called++;
        });

        $r = [];
        $agent->url = 'http://jsonplaceholder.typeicode.com/posts/1';

        for($i = 0; $i<20; $i++) {
            $r[] = $agent->newRequest();
        }

        $agent->execute();

        $this->assertEquals(20, $called);
        foreach($r as $req) {
            $this->assertNotNull($req->response);
        }

        var_dump($r[19]->response);
        var_dump($r[19]->url);
    }
}