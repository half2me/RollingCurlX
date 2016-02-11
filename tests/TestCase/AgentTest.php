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

        $r1 = $agent->newRequest('https://google.com');

        $agent->execute();

        $this->assertEquals(1, $called);
    }
}