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
$request = $agent->newRequest('http://myurl.com'); // URL can optionally be set here
$request->url = 'http://myurl.com'; // or here
$request->timeout = 5000; // We can set different timeout values (in msec) for each request
$request->post = ['Agents' => 'AreCool']; // We can add post fields as arrays
$request->post = ['MoreAgents' => 'AreCooler']; // This will be appended to the post values already set
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
$agent->post = ['AllAgents' => 'AreCool'];
$request = $agent->newRequest();

echo $request->post['AllAgents']; // this will output 'AreCool'

// of course we can always overwrite this:
$request->post = ['AllAgents' => 'AreSuperDuperCool']; // This will overwrite that post value
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
```

### Issues
If you find any issues please let me know.

Enjoy.
