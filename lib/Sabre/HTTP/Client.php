<?php

namespace Sabre\HTTP;

/**
 * This class provides a simple HTTP client, based on Curl.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Client implements ClientInterface {

    /**
     * Base Url
     *
     * This url is used to determine absolute paths, in case a relative url is
     * given in the request.
     *
     * @var string
     */
    protected $url;

    /**
     * HTTP Auth username
     *
     * @var string
     */
    protected $userName;

    /**
     * HTTP Auth password
     *
     * @var string
     */
    protected $password;

    /**
     * HTTP Proxy
     *
     * @var string
     */
    protected $proxy;

    /**
     * The authentication type we're using.
     *
     * This is a bitmask of AUTH_BASIC and AUTH_DIGEST.
     *
     * If DIGEST is used, the client makes 1 extra request per request, to get
     * the authentication tokens.
     *
     * @var int
     */
    protected $authType;

    /**
     * Reference to the curl object.
     *
     * @var resource
     */
    protected $curl;

    /**
     * Creates the client.
     *
     * This object allows for the following settings:
     *   * url - This is used as a base uri to calculate absolute url.
     *   * userName - HTTP Auth username (optional)
     *   * password - HTTP Auth password (optional)
     *   * proxy - Proxy settings in hostname:port format (optional).
     *   * authType - AUTH_BASIC, AUTH_DIGEST or both.
     *
     * @return void
     */
    public function __construct(array $settings) {

        if (!isset($settings['url'])) {
            throw new \InvalidArgumentException('The \'url\' setting must be provided');
        }

        $validSettings = array(
            'url',
            'userName',
            'password',
            'proxy',
            'authType',
        );

        foreach($validSettings as $validSetting) {
            if (isset($settings[$validSetting])) {
                $this->$validSetting = $settings[$validSetting];
            }
        }
        if (is_null($this->authType)) {
            $this->authType = self::AUTH_BASIC | self::AUTH_DIGEST;
        }

        // Pre-creating the curl object.
        $this->curl = curl_init();

    }

    /**
     * Performs the HTTP request
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function request(RequestInterface $request) {

        $url = $this->getAbsoluteUrl($request->getUrl());

        $body = $request->getBody();
        if (!is_null($body)) {
            $body = stream_get_contents($body);
        }

        $curlSettings = array(
            CURLOPT_RETURNTRANSFER => true,
            // Return headers as part of the response
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => $body,
            // Automatically follow redirects
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_URL => $url,
        );

        switch ($request->getMethod()) {
            case 'HEAD' :

                // do not read body with HEAD requests (this is neccessary because cURL does not ignore the body with HEAD
                // requests when the Content-Length header is given - which in turn is perfectly valid according to HTTP
                // specs...) cURL does unfortunately return an error in this case ("transfer closed transfer closed with
                // ... bytes remaining to read") this can be circumvented by explicitly telling cURL to ignore the
                // response body
                $curlSettings[CURLOPT_NOBODY] = true;
                $curlSettings[CURLOPT_CUSTOMREQUEST] = 'HEAD';
                break;

            default:
                $curlSettings[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
                break;

        }

        // Adding HTTP headers
        $nHeaders = array();
        foreach($request->getHeaders() as $key=>$value) {

            $nHeaders[] = $key . ': ' . $value;

        }
        $curlSettings[CURLOPT_HTTPHEADER] = $nHeaders;

        if ($this->proxy) {
            $curlSettings[CURLOPT_PROXY] = $this->proxy;
        }

        if ($this->userName && $this->authType) {
            $curlType = 0;
            if ($this->authType & self::AUTH_BASIC) {
                $curlType |= CURLAUTH_BASIC;
            }
            if ($this->authType & self::AUTH_DIGEST) {
                $curlType |= CURLAUTH_DIGEST;
            }
            $curlSettings[CURLOPT_HTTPAUTH] = $curlType;
            $curlSettings[CURLOPT_USERPWD] = $this->userName . ':' . $this->password;
        }

        list(
            $response,
            $curlInfo,
            $curlErrNo,
            $curlError
        ) = $this->curlRequest($curlSettings);

        $headerBlob = substr($response, 0, $curlInfo['header_size']);
        $response = substr($response, $curlInfo['header_size']);

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

        if ($curlErrNo) {
            throw new ClientException('[CURL] Error while making request: ' . $curlError . ' (error code: ' . $curlErrNo . ')');
        }

        $responseStream = fopen('php://memory','r+');
        fwrite($responseStream, $response);
        rewind($responseStream);

        $responseObj = new Response(
            $curlInfo['http_code'],
            $headers,
            $responseStream
        );

        return $responseObj;

    }

    // @codeCoverageIgnoreStart
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
    protected function curlRequest($settings) {

        curl_setopt_array($this->curl, $settings);

        return array(
            curl_exec($this->curl),
            curl_getinfo($this->curl),
            curl_errno($this->curl),
            curl_error($this->curl)
        );

    }
    // @codeCoverageIgnoreEnd

    /**
     * Returns the full url based on the given url (which may be relative). All
     * urls are expanded based on the base url as given by the server.
     *
     * @param string $url
     * @return string
     */
    protected function getAbsoluteUrl($url) {


        // If the url starts with http:// or https://, the url is already absolute.
        if (preg_match('/^http(s?):\/\//', $url)) {
            return $url;
        }

        // If the url starts with a slash, we must calculate the url based off
        // the root of the base url.
        if (strpos($url,'/') === 0) {
            $parts = parse_url($this->url);
            return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port'])?':' . $parts['port']:'') . $url;
        }

        // Otherwise...
        return $this->url . $url;

    }

}
