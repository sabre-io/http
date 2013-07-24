#sabre/http library

This library provides a toolkit to make working with the HTTP protocol easier.

Most PHP scripts run within a HTTP request, but accessing information about
the request, and doing the response in PHP is simply put badly implemented.

There's bad practices, inconsistencies and confusion. This library is
therefore effectively a wrapper around the following PHP constructs:

For Input:

* `$_GET`
* `$_POST`
* `$_SERVER`
* `php://input` or `$HTTP_RAW_POST_DATA`.

For output:

* `php://output` or `echo`.
* `header()`

What this library provides, is a `Request` object, and a `Response` object.

The objects are extendable and easily mockable.

## Installation

Make sure you have [composer][1] installed. In your project directory, create,
or edit a `composer.json` file, and make sure it contains something like this:


```json
{
    "require" : {
        "sabre/http" : "2.0@alpha"
    }
}
```

After that, just hit `composer install` and you should be rolling.

## Quick history

This library came to existence in 2009, as a part of the [SabreDAV][2]
project, which uses it heavily.

It got split off into a separate library to make it easier to manage
releases and hopefully giving it use outside of the scope of just SabreDAV.

Although completely independently developed, this library has a LOT of
overlap with [symfony's HttpFoundation][3].

Said library does a lot more stuff and is significantly more popular,
so if you are looking for something to fulfill this particular requirement,
I'd recommend also considering [HttpFoundation][3].

## Usage

First and foremost, this library wraps the terrible, terrible superglobals.
The easiest way to instantiate a request object is as follows:

```php

use Sabre\HTTP;

include 'vendor/autoload.php';

$request = HTTP\Request::createFromPHPRequest();
```

This line should only happen once in your entire application. Everywhere else
you should pass this request object around using dependency injection.

You should always typehint on it's interface:

```php
function handleRequest(HTTP\RequestInterface $request) {

    // Do something with this request :)

}

```

A response object you can just create as such:


```php
use Sabre\HTTP;

include 'vendor/autoload.php';

$response = new HTTP\Response();
$response->setStatus(201); // created !
$response->setHeader('X-Foo', 'bar');
$response->body(
    'success!'
);

```

After you fully constructed your response, you must call:

```php
$response->send();
```

This line should generally also appear once in your application (at the very
end).

### Client

This package also contains a simple wrapper around [cURL][4], which will allow
you to write simple clients, using the `Request` and `Response` objects you're
already familiar with.

```php
use Sabre\HTTP;

include 'vendor/autoload.php';

$request = new HTTP\Request('GET', 'http://example.org/');
$request->setHeader('X-Foo', 'Bar');

$client = new HTTP\Client();
$response = $client->send($request);

echo $response->getBody();
```

The client emits 3 event using [sabre/event][5]. `beforeRequest`,
`afterRequest` and `error`.

```php
$client = new HTTP\Client();
$client->on('beforeRequest', function($request) {

    // You could use beforeRequest to for example inject a few extra headers.
    // into the Request object.

});

$client->on('afterRequest', function($request, $response) {

    // The afterRequest event could be a good time to do some logging, or
    // do some rewriting in the response.

});

$client->on('error', function($request, $response, &$retry, $retryCount) {

    // The error event is triggered for every response with a HTTP code higher
    // than 399.

});

$client->on('error:401', function($request, $response, &$retry, $retryCount) {

    // You can also listen for specific error codes. This example shows how
    // to inject HTTP authentication headers if a 401 was returned.

    if ($retryCount > 1) {
        // We're only going to retry exactly once.
    }

    $request->setHeader('Authorization', 'Basic xxxxxxxxxx');
    $retry = true;

});
```



[1]: http://getcomposer.org/
[2]: http://code.google.com/p/sabredav
[3]: https://github.com/symfony/HttpFoundation
[4]: http://uk3.php.net/curl
[5]: https://github.com/fruux/sabre-event
