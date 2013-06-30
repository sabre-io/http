<?php

namespace Sabre\HTTP;

/**
 * The Request class represents a single HTTP request.
 *
 * You can either simply construct the object from scratch, or if you would
 * like to create the request from the $_SERVER array, use the
 * createFromServerArray static method.
 *
 * @copyright Copyright (C) 2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Request extends Message implements RequestInterface {

    /**
     * HTTP Method
     *
     * @var string
     */
    protected $method;

    /**
     * Request Url
     *
     * @var string
     */
    protected $url;


    /**
     * An array containing the raw _SERVER array.
     *
     * @var array
     */
    protected $rawServerData;

    /**
     * Creates the request object
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param resource $body
     */
    public function __construct($method = null, $url = null, array $headers = null, $body = null) {

        if (!is_null($method))      $this->setMethod($method);
        if (!is_null($url))         $this->setUrl($url);
        if (!is_null($headers))     $this->setHeaders($headers);
        if (!is_null($body))        $this->setBody($body);

    }

    /**
     * This static method will create a new Request object, based on the
     * current PHP request.
     *
     * @param resource $body
     * @return Request
     */
    static public function createFromPHPRequest() {

        $r = self::createFromServerArray($_SERVER);
        $r->setBody(fopen('php://input','r'));
        return $r;

    }

    /**
     * This static method will create a new Request object, based on a PHP
     * $_SERVER array.
     *
     * @param array $serverArray
     * @param resource $body
     * @return Request
     */
    static public function createFromServerArray(array $serverArray) {

        $headers = array();
        $method = null;
        $url = null;
        $httpVersion = '1.1';

        foreach($serverArray as $key=>$value) {

            switch($key) {

                case 'SERVER_PROTOCOL' :
                    if ($value==='HTTP/1.0') {
                        $httpVersion = '1.0';
                    }
                    break;
                case 'REQUEST_METHOD' :
                    $method = $value;
                    break;
                case 'REQUEST_URI' :
                    $url = $value;
                    break;

                // These sometimes should up without a HTTP_ prefix
                case 'CONTENT_TYPE' :
                    $headers['Content-Type'] = $value;
                    break;
                case 'CONTENT_LENGTH' :
                    $headers['Content-Length'] = $value;
                    break;

                // mod_php on apache will put credentials in these variables.
                // (fast)cgi does not usually do this, however.
                case 'PHP_AUTH_USER' :
                    if (isset($serverArray['PHP_AUTH_PW'])) {
                        $headers['Authorization'] = 'Basic ' . base64_encode($value . ':' . $serverArray['PHP_AUTH_PW']);
                    }
                    break;

                // Similarly, mod_php may also screw around with digest auth.
                case 'PHP_AUTH_DIGEST' :
                    $headers['Authorization'] = 'Digest ' . $value;
                    break;

                // Apache may prefix the HTTP_AUTHORIZATION header with
                // REDIRECT_, if mod_rewrite was used.
                case 'REDIRECT_HTTP_AUTHORIZATION' :
                    $headers['Authorization'] = $value;
                    break;

                default :
                    if (substr($key,0,5)==='HTTP_') {
                        // It's a HTTP header

                        // Normalizing it to be prettier
                        $header = strtolower(substr($key,5));

                        // Transforming dashes into spaces, and uppercasing
                        // every first letter.
                        $header = ucwords(str_replace('_', ' ', $header));

                        // Turning spaces into dashes.
                        $header = str_replace(' ', '-', $header);
                        $headers[$header] = $value;

                    }
                    break;


            }

        }

        $r = new self($method, $url, $headers);
        $r->setHttpVersion($httpVersion);
        $r->setRawServerData($serverArray);
        return $r;

    }

    /**
     * Returns the current HTTP method
     *
     * @return string
     */
    public function getMethod() {

        return $this->method;

    }

    /**
     * Sets the HTTP method
     *
     * @param string $method
     * @return void
     */
    public function setMethod($method) {

        $this->method = $method;

    }

    /**
     * Returns the request url.
     *
     * @return string
     */
    public function getUrl() {

        return $this->url;

    }

    /**
     * Sets the request url.
     *
     * @param string $url
     * @return void
     */
    public function setUrl($url) {

        $this->url = $url;

    }

    /**
     * Returns an item from the _SERVER array.
     *
     * If the value does not exist in the array, null is returned.
     *
     * @param string $valueName
     * @return string|null
     */
    public function getRawServerValue($valueName) {

        if (isset($this->rawServerData[$valueName])) {
            return $this->rawServerData[$valueName];
        }

    }

    /**
     * Sets the _SERVER array.
     *
     * @param array $data
     * @return void
     */
    public function setRawServerData(array $data) {

        $this->rawServerData = $data;

    }

}
