# CurlX

Curl X is a fork of [RollingCurlX](https://github.com/marcushat/RollingCurlX). It aims at making concurrent http requests in PHP as easy as possible. I created this fork to make this wrapper installable via composer, and it is now using PSR-4

####License
MIT

#### Requirements
PHP 5.4+

##How to Use

First we initialize an agent with the maximum number of concurrent requests we want open at a time.
All requests after this will be queued until one completes.

```php
use CurlX\Agent;
$agent = new Agent(10);
```

Next we create/add a request to the queue
```php
$request = $agent->request('http://myurl.com'); // URL can optionally be set here
$request->url = 'http://myurl.com'; // or here
$request->timeout = 5000; // We can set different timeout values (in msec) for each request
$request->post = ['Agents' => 'AreCool']; // We can add post fields as arrays
$request->post = ['MoreAgents' => 'AreCooler']; // This will be appended to the post values already set
$request->headers = ['agents', 'are', 'super' 'cool']; // Headers can easily be set
$request->headers = ['more-agents', 'are', 'even', 'coolers']; // These will be appended to the header list
$request->options = ['CURLOPT_SOME_OPTION' => 'your-value']; // Advanced options can be set for cURL
$request->options = ['CURLOPT_SOME_OTHER_OPTION' => 'your-other-value']; // Chain these up, or add many in one array

$url = 'http://www.google.com/search?q=apples';
$post_data = ['user' => 'bob', 'token' => 'dQw4w9WgXcQ']; //set to NULL if not using POST
$user_data = ['foo', $whatever];
$options = [CURLOPT_FOLLOWLOCATION => false];

$curlX->addRequest($url, $post_data, 'callback_functn', $user_data, $options, $headers);
```

The callback function should look like this:
```php
function callback_functn($response, $url, $request_info, $user_data, $time) {
    $time; //how long the request took in milliseconds (float)
    $request_info; //returned by curl_getinfo($ch)
}
```

Send the requests. Blocks until all requests complete or timeout.
```php
$curlX->execute();
```

See? Easy. Thats pretty much it for a simple request.

There's more if you need it though...
```php
//Set a timeout on all requests:
$curlX->setTimeout(3000); //in milliseconds

//To set options for all requests(will be overridden by individual request options):
$curlX->setOptions([$curl_options]);

//To do the same with http headers:
$curlX->setHeaders(['Content-type: application/xml', 'Authorization: gfhjui']);
```

### Issues
If you find any issues please let me know.

Enjoy.
