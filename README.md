# CurlX  
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://travis-ci.org/half2me/curlx.svg?branch=master)](https://travis-ci.org/half2me/curlx)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/half2me/curlx/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/half2me/curlx/?branch=master)
[![codecov.io](https://codecov.io/github/half2me/curlx/coverage.svg?branch=master)](https://codecov.io/github/half2me/curlx?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/half2me/curlx.svg?style=flat-square)](https://packagist.org/packages/half2me/curlx)
[![Latest Stable Version](https://img.shields.io/packagist/v/half2me/curlx.svg?style=flat-square&label=stable)](https://packagist.org/packages/half2me/curlx)
[![Latest Unstable Version](https://img.shields.io/packagist/vpre/half2me/curlx.svg?style=flat-square&label=unstable)](https://packagist.org/packages/half2me/curlx)  
Curl X is a fork of [RollingCurlX](https://github.com/marcushat/RollingCurlX). At fist I only created this fork to make it installable via composer for a project I was working on.
Now it is a modern, easy-to-use, awesome wrapper for cUrl Multi Handler. With Agents and Requests, take a look at how easy everything has become.

####License
MIT

#### Requirements
PHP 5.6+

# Installing
Installing is easy with composer. Just do
`composer require half2me/curlx:^1.0`

##How to Use

First we initialize an agent with the maximum number of concurrent requests we want open at a time.
All requests after this will be queued until one completes.

```php
use CurlX\Agent;

$agent = new Agent(10);
```

Next we create/add a request to the queue
```php
$request = $agent->newRequest('http://myurl.com'); // URL can optionally be set here
$request->url = 'http://myurl.com'; // or here
$request->timeout = 5000; // We can set different timeout values (in msec) for each request
$request->post_data = ['Agents' => 'AreCool']; // We can add post fields as arrays
$request->post_data = ['MoreAgents' => 'AreCooler']; // This will be appended to the post values already set
$request->headers = ['Content-type' => 'agent/xml', 'Authorization' => 'ninja-stuff']; // Headers can easily be set
$request->headers = ['Agent-type: Ninja']; // These will be appended to the header list
$request->options = ['CURLOPT_SOME_OPTION' => 'your-value']; // Advanced options can be set for cURL
$request->options = ['CURLOPT_SOME_OTHER_OPTION' => 'your-other-value']; // Chain these up, or add many in one array

// The Agent already has this request in his queue, so we don't need to do anything after modifying requests options.
```

Most of the values that can be set on individual Requests can also be set for an agent
When an agent has these values set, any requests created by that agent, will have these parameters set;
If we have many requests using similar headers, urls, or timeout values, we can set these once in the Agent,
and use them in all of the requests.
For example:
```php
$agent->post_data = ['AllAgents' => 'AreCool'];
$request = $agent->newRequest();

echo $request->post_data['AllAgents']; // this will output 'AreCool'

// of course we can always overwrite this:
$request->post_data = ['AllAgents' => 'AreSuperDuperCool']; // This will overwrite that post value
```

Once we have our agent loaded up with requests
```php
$request1 = $agent->newRequest();
$request2 = $agent->newRequest();
```
We can start executing them with:
```php
$agent->execute();
```

As a request finishes, it will fire an event which we need to hook onto, before we start the agent.
For this we need to register one or more callback functions with either the agent (to use the same for all requests)
or we can register a separate callback function for each request.
```php
$request1->addListener('myCallbackFunction'); // For a single request
$agent->addListener('myCallbackFunction'); // For all requests to use the same callback
// Note, this will only apply to requests made after the addListener() was called.

// You can use anonymous functions for callbacks like this:
$request->addListener(function(CurlX\RequestInterface $request) {
    // Each listener (or callback function) will upon request completion receieve
    // in the function parameter, the completed request object
    
    echo $request->response; // Response is stored here
    echo $request->http_code; // Get the http code of the reply
});
```


### Issues
If you find any issues please let me know. Submit an issue or PR on github

Enjoy.
