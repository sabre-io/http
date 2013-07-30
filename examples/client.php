<?php

/**
 * This example shows how to make a HTTP request with the Request and Response
 * objects.
 *
 * @copyright Copyright (C) 2009-2013 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

use
    Sabre\HTTP\Request,
    Sabre\HTTP\Client;


// Find the autoloader
$paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',

];

foreach($paths as $path) {
    if (file_exists($path)) {
        include $path;
        break;
    }
}

// Constructing the request.

$request = new Request('GET', 'http://feeds.feedburner.com/bijsterespoor');

$client = new Client();
//$client->addCurlSetting(CURLOPT_PROXY,'localhost:8888');
$response = $client->send($request);

echo "Response: " . $response->getStatus() . "\n";
echo "Headers:\n";
print_r($response->getHeaders());
