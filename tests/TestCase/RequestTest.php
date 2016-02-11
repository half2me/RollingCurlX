<?php
/**
 * Created by PhpStorm.
 * User: lejla
 * Date: 2016.02.10.
 * Time: 17:51
 */

namespace CurlX\Tests;


use CurlX\Request;
use CurlX\RequestInterface;
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
        $this->assertEquals($post2 + $post, $request->post_data);
    }

    public function testNotify()
    {
        $request = new Request();
        $called1 = false;
        $called2 = false;
        $r1 = null;
        $r2 = null;

        $request->addListener(function(RequestInterface $var) use (&$called1, &$r1) {
            $called1 = true;
            $r1 = $var;
        });

        $request->addListener(function(RequestInterface $var) use (&$called2, &$r2) {
            $called2 = true;
            $r2 = $var;
        });

        $request->callBack([]);

        $this->assertTrue($called1, 'Callback 1 was not notified on request completion!');
        $this->assertInstanceOf('CurlX\RequestInterface', $r1, 'Callback 1 did not receive the request object');
        $this->assertTrue($called2, 'Callback 2 was not notified on request completion!');
        $this->assertInstanceOf('CurlX\RequestInterface', $r2, 'Callback 2 did not receive the request object');
    }

    public function testHeaders()
    {
        $request = new Request();

        $header = ['a' => 'aaa', 'b' => 'bbb'];
        $normalHeader = ['a: aaa', 'b: bbb'];

        $request->headers = $header;
        $this->assertEquals($header, $request->headers);

        $this->assertArrayHasKey(CURLOPT_HTTPHEADER, $request->options);
        $this->assertEquals($normalHeader, $request->options[CURLOPT_HTTPHEADER]);

        $header2 = ['b' => 'BBB', 'c' => 'CCC'];
        $normalOfBothHeaders = ['b: BBB', 'c: CCC', 'a: aaa'];
        $request->headers = $header2;
        $this->assertEquals($header2 + $header, $request->headers);

        $this->assertArrayHasKey(CURLOPT_HTTPHEADER, $request->options);
        $this->assertArraySubset($normalOfBothHeaders, $request->options[CURLOPT_HTTPHEADER]);
    }

    public function testOptions()
    {
        $request = new Request();

        $opt = [CURLOPT_CRLF => 'test', CURLOPT_AUTOREFERER => 'test'];
        $request->options = $opt;

        $this->assertArrayHasKey(CURLOPT_CRLF, $request->options);
        $this->assertArrayHasKey(CURLOPT_AUTOREFERER, $request->options);
        $this->assertArraySubset($opt, $request->options);

        $opt2 = [CURLOPT_AUTOREFERER => 'no-test', CURLOPT_BINARYTRANSFER => 'no-test'];
        $request->options = $opt2;

        $this->assertArrayHasKey(CURLOPT_CRLF, $request->options);
        $this->assertArrayHasKey(CURLOPT_AUTOREFERER, $request->options);
        $this->assertArrayHasKey(CURLOPT_BINARYTRANSFER, $request->options);
        $this->assertArraySubset($opt2 + $opt, $request->options);
    }

    public function testHandle()
    {
        $request = new Request('http://example.com');
        $ch = $request->handle;

        $this->assertNotNull($ch);
        $info = curl_getinfo($ch);

        $this->assertEquals($request->url, $info['url']);
    }
}