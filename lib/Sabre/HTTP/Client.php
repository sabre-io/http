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
 * This client emits the following events:
 *   beforeRequest(RequestInterface $request)
 *   afterRequest(RequestInterface $request, ResponseInterface $response)
 *   error(RequestInterface $request, ResponseInterface $response, bool &$retry, int $retryCount)
 *
 * The beforeRequest event allows you to do some last minute changes to the
 * request before it's done, such as adding authentication headers.
 *
 * The afterRequest event will be emitted after the request is completed
 * succesfully.
 *
 * If a HTTP error is returned (status code higher than 399) the error event is
 * triggered. It's possible using this event to retry the request, by setting
 * retry to true.
 *
 * The amount of times a request has retried is passed as $retryCount, which
 * can be used to avoid retrying indefinitely. The first time the event is
 * called, this will be 0.
 *
 * It's also possible to intercept specific http errors, by subscribing to for
 * example 'error:401'.
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
            CURLOPT_POSTREDIR => 3,
        ];

    }

    /**
     * Sends a request to a HTTP server, and returns a response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function send(RequestInterface $request) {

        $this->emit('beforeRequest', [$request]);

        $retryCount = 0;

        do {

            $response = $this->doRequest($request);

            $retry = false;
            $code = (int)$response->getStatus();

            // This was a HTTP error
            if ($code > 399) {

                $this->emit('error', [$request, $response, &$retry, $retryCount]);
                $this->emit('error:' . $code, [$request, $response, &$retry, $retryCount]);

            }
            if ($retry) {
                $retryCount++;
            }

        } while ($retry);

        $this->emit('afterRequest', [$request, $response]);

        return $response;

    }

    /**
     * This method is responsible for performing a single request.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    protected function doRequest(RequestInterface $request) {

        $settings = $this->curlSettings;

        switch($request->getMethod()) {
            case 'HEAD' :
                $settings[CURLOPT_NOBODY] = true;
                $settings[CURLOPT_CUSTOMREQUEST] = 'HEAD';
                $settings[CURLOPT_POSTFIELDS] = '';
                $settings[CURLOPT_PUT] = false;
                break;
            case 'GET' :
                $settings[CURLOPT_CUSTOMREQUEST] = 'GET';
                $settings[CURLOPT_POSTFIELDS] = '';
                $settings[CURLOPT_PUT] = false;
                break;
            default :
                $body = $request->getBody(MessageInterface::BODY_RAW);
                if (is_resource($body)) {
                    // This needs to be set to PUT, regardless of the actual
                    // method used. Without it, INFILE will be ignored for some
                    // reason.
                    $settings[CURLOPT_PUT] = true;
                    $settings[CURLOPT_INFILE] = $request->getBody();
                } else {
                    // Else, it's a string.
                    $settings[CURLOPT_POSTFIELDS] = $body;
                }
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

        if ($curlErrNo) {
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
    
    public function __destruct() {
        if ($this->curlHandle) {
            curl_close($this->curlHandle);
        }
    }
    // @codeCoverageIgnoreEnd

}
