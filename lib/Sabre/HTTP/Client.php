<?php

namespace Sabre\HTTP;

use
    Sabre\Event\EventEmitter;

/**
 * A rudimentary HTTP client.
 *
 * This object wraps PHP's curl extension and provides an easy way to send it a
 * Request object, and return a Response object.
 *
 * This is by no means intended as the next best HTTP client, but it does the
 * job and provides a simple integration with the rest of sabre/http.
 *
 * @copyright Copyright (C) 2009-2013 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Client extends EventEmitter {

    /**
     * List of curl settings
     *
     * @var array
     */
    protected $curlSettings = [];

    /**
     * Initializes the client.
     *
     * @return void
     */
    public function __construct() {

        $this->curlSettings = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
        ];

    }

    /**
     * Sends a request to a HTTP server, and returns a response.
     *
     * @param RequestInterface $request
     * @return Response
     */
    public function send(RequestInterface $request) {

        $this->emit('beforeRequest', [$request]);

        $settings = $this->curlSettings;

        switch($request->getMethod()) {
            case 'HEAD' :
                $settings[CURLOPT_NOBODY] = true;
                $settings[CURLOPT_CUSTOMREQUEST] = 'HEAD';
                break;
            case 'GET' :
                break;
            default :
                // This needs to be set to PUT, regardless of the actual method.
                $settings[CURLOPT_PUT] = true;
                $settings[CURLOPT_INFILE] = $request->getBody();
                $settings[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
                break;

        }

        $nHeaders = [];
        foreach($request->getHeaders() as $key=>$value) {

            $nHeaders[] = $key . ': ' . $value;

        }
        $settings[CURLOPT_HTTPHEADER] = $nHeaders;
        $settings[CURLOPT_URL] = $request->getUrl();

        list(
            $response,
            $curlInfo,
            $curlErrNo,
            $curlError
        ) = $this->curlRequest($settings);

        if ($curlErroNo) {
            throw new ClientException($curlError, $curlErrNo);
        }

        $headerBlob = substr($response, 0, $curlInfo['header_size']);
        $responseBody = substr($response, $curlInfo['header_size']);

        unset($response);

        // In the case of 100 Continue, or redirects we'll have multiple lists
        // of headers for each separate HTTP response. We can easily split this
        // because they are separated by \r\n\r\n
        $headerBlob = explode("\r\n\r\n", trim($headerBlob, "\r\n"));

        // We only care about the last set of headers
        $headerBlob = $headerBlob[count($headerBlob)-1];

        // Splitting headers
        $headerBlob = explode("\r\n", $headerBlob);

        $headers = array();
        foreach($headerBlob as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts)==2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }

        $response = new Response();
        $response->setStatus($curlInfo['http_code']);
        $response->setHeaders($headers);
        $response->setBody($responseBody);

        $this->emit('afterRequest', [$request, $response]);

        return $response;

    }

    /**
     * Adds a CURL setting.
     *
     * @param int $name
     * @param mixed $value
     * @return void
     */
    public function addCurlSetting($name, $value) {

        $this->curlSettings[$name] = $value;

    }

    /**
     * Cached curl handle.
     *
     * By keeping this resource around for the lifetime of this object, things
     * like persistent connections are possible.
     *
     * @var resource
     */
    private $curlHandle;

    /**
     * Wrapper for all curl functions.
     *
     * The only reason this was split out in a separate method, is so it
     * becomes easier to unittest.
     *
     * @param string $url
     * @param array $settings
     * @return array
     */
    // @codeCoverageIgnoreStart
    protected function curlRequest($settings) {

        if (!$this->curlHandle) {
            $this->curlHandle = curl_init();
        }

        curl_setopt_array($this->curlHandle, $settings);

        return [
            curl_exec($this->curlHandle),
            curl_getinfo($this->curlHandle),
            curl_errno($this->curlHandle),
            curl_error($this->curlHandle)
        ];

    }
    // @codeCoverageIgnoreEnd

}
