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
     * Creates the request object
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param resource $body
     */
    public function __construct($method, $url, array $headers, $body = null) {

        $this->setMethod($method);
        $this->setUrl($url);
        $this->setHeaders($headers);

        if (!is_null($body)) $this->setBody($body);

    }

    /**
     * This static method will create a new Request object, based on a PHP
     * $_SERVER array.
     *
     * @param array $serverArray
     * @param resource $body
     * @return void
     */
    static public function createFromServerArray(array $serverArray, $body = null) {

        $headers = array();
        $method = null;
        $url = null;

        foreach($serverArray as $key=>$value) {

            switch($key) {

                case 'REQUEST_METHOD' :
                    $method = $value;
                    break;
                case 'REQUEST_URI' :
                    $uri = $value;
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
                case 'REDIRECT_HTTP_AUTHORIZATION'
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
                        $header = str_replace(' ', '_', $header);
                        $headers[$header] = $value;

                    }
                    break;


            }

        }
        return new self($method, $uri, $headers, $body);

    }

    /**
     * Returns the current HTTP method
     *
     * @return string
     */
    public function getMethod() {

        return $method;

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

}
