<?php
/**
 * Created by PhpStorm.
 * User: lejla
 * Date: 2016.02.10.
 * Time: 17:51
 */

namespace CurlX\Tests;


use CurlX\Request;
use PHPUnit_Framework_TestCase;

class RequestTest extends PHPUnit_Framework_TestCase
{
    public function testUrl()
    {
        $request = new Request('http://url.com');
        $this->assertEquals('http://url.com', $request->url);

        $request->url = 'http://url2.com';
        $this->assertEquals('http://url2.com', $request->url);

        $request->url = 'badurl';
        $this->assertEquals('http://url2.com', $request->url);
    }

    public function testPostFieldsBuilder()
    {
        $request = new Request();

        // No post values yet, so it should default to GET
        $this->assertArrayNotHasKey(CURLOPT_POST, $request->options);
        $this->assertEmpty($request->post_data);

        // With post values
        $post = ['username' => 'mike', 'password' => 'pass'];
        $request->post_data = $post;
        $this->assertArrayHasKey(CURLOPT_POST, $request->options);
        $this->assertEquals($post, $request->post_data);

        // Add more post fields
        $post2 = ['otherdata' => 'newvalue', 'username' => 'stacey'];
        $request->post_data = $post2;
        $this->assertArrayHasKey(CURLOPT_POST, $request->options);
        $this->assertEquals($post + $post2, $request->post_data);
    }

    public function testTimer()
    {
        $request = new Request();

        $this->assertNull($request->time);

        $request->startTimer();
        $this->assertNull($request->time);

        $request->stopTimer();
        $this->assertNotNull($request->time);
        $this->assertTrue($request->time >= 0);
    }

    public function testNotify()
    {
        $request = new Request();
        $called1 = false;
        $called2 = false;
        $r1 = null;
        $r2 = null;

        $request->addListener(function($var) use (&$called1, &$r1) {
            $called1 = true;
            $r1 = $var;
        });

        $request->addListener(function($var) use (&$called2, &$r2) {
            $called2 = true;
            $r2 = $var;
        });

        $request->callBack([]);

        $this->assertTrue($called1, 'Callback 1 was not notified on request completion!');
        $this->assertInstanceOf('CurlX\RequestInterface', $r1, 'Callback 1 did not receive the request object');
        $this->assertTrue($called2, 'Callback 2 was not notified on request completion!');
        $this->assertInstanceOf('CurlX\RequestInterface', $r2, 'Callback 2 did not receive the request object');
    }
}