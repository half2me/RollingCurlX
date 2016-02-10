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
    }
}