<?php

namespace Sabre\HTTP;

use InvalidArgumentException;

/**
 * The Request class represents a single HTTP request.
 *
 * You can either simply construct the object from scratch, or if you need
 * access to the current HTTP request, use Sapi::getRequest.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Request extends Message implements RequestInterface {

    use BC\RequestTrait;

    /**
     * Creates the request object
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param resource $body
     */
    function __construct($method = null, $url = null, array $headers = null, $body = null) {

        if (is_array($method)) {
            throw new InvalidArgumentException('The first argument for this constructor should be a string or null, not an array. Did you upgrade from sabre/http 1.0 to 2.0?');
        }
        if (!is_null($method))      $this->setMethod($method);
        if (!is_null($url))         $this->setUrl($url);
        if (!is_null($headers))     $this->setHeaders($headers);

        if (is_string($body) || is_resource($body)) {
            $body = new Stream($body);
        }
        if (!is_null($body))        $this->setBody($body);

    }

    /**
     * HTTP Method
     *
     * @var string
     */
    protected $method;

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    function getMethod() {

        return $this->method;

    }

    /**
     * Sets the HTTP method to be performed on the resource identified by the
     * Request-URI.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * @param string $method Case-insensitive method.
     * @return void
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    function setMethod($method) {

        $this->method = $method;

    }

    /**
     * Request Url
     *
     * @var string
     */
    protected $url;

    /**
     * Retrieves the absolute request URL.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return string|object Returns the URL as a string, or an object that
     *    implements the `__toString()` method. The URL must be an absolute URI
     *    as specified in RFC 3986.
     */
    function getUrl() {

        return $this->url;

    }

    /**
     * Sets the request URL.
     *
     * The URL MUST be a string, or an object that implements the
     * `__toString()` method. The URL must be an absolute URI as specified
     * in RFC 3986.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param string|object $url Request URL.
     * @return void
     * @throws \InvalidArgumentException If the URL is invalid.
     */
    function setUrl($url) {

        $this->url = $url;

    }

    /**
     * A $_COOKIES-like array.
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The assumption is these are injected during instantiation, typically
     * from PHP's $_COOKIE superglobal. The data IS NOT REQUIRED to come from
     * $_COOKIE, but MUST be compatible with the structure of $_COOKIE.
     *
     * @return array
     */
    function getCookieParams() {

        return $this->cookies;

    }

    /**
     * Set cookie parameters.
     *
     * Allows a library to set the cookie parameters. Use cases include
     * libraries that implement additional security practices, such as
     * encrypting or hashing cookie values; in such cases, they will read
     * the original value, filter them, and re-inject into the incoming
     * request.
     *
     * The value provided MUST be compatible with the structure of $_COOKIE.
     *
     * @param array $cookies Cookie values
     * @return void
     * @throws \InvalidArgumentException For invalid cookie parameters.
     */
    function setCookieParams(array $cookies) {

        $this->cookies = $cookies;

    }

    /**
     * Returns the list of query parameters.
     *
     * This is equivalent to PHP's $_GET superglobal.
     *
     * @return array
     */
    function getQueryParams() {

        $url = $this->getUrl();
        if (($index = strpos($url,'?'))===false) {
            return [];
        } else {
            parse_str(substr($url, $index+1), $queryParams);
            return $queryParams;
        }

    }

    /**
     * A $_FILES-like array.
     */
    protected $fileParams = [];

    /**
     * Retrieve the upload file metadata.
     *
     * This method MUST return file upload metadata in the same structure
     * as PHP's $_FILES superglobal.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_FILES superglobal, or MAY be derived from other sources.
     *
     * @return array Upload file(s) metadata, if any.
     */
    function getFileParams() {

        return $this->fileParams;

    }

    /**
     * Populates the $_FILES array.
     *
     * This should generally only be done by the Sapi.
     */
    function setFileParams(array $files) {

        $this->fileParams = $files;

    }


    /**
     * A $_POST-like array
     *
     * @var array
     */
    protected $bodyParams = [];

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request body can be deserialized to an array, this method MAY be
     * used to retrieve them. These MAY be injected during instantiation from
     * PHP's $_POST superglobal. The data IS NOT REQUIRED to come from $_POST,
     * but MUST be an array.
     *
     * In cases where the request content cannot be coerced to an array, the
     * parent getBody() method should be used to retrieve the body content.
     *
     * @return array The deserialized body parameters, if any.
     */
    function getBodyParams() {

        return $this->bodyParams;

    }

    /**
     * Set the request body parameters.
     *
     * If the body content can be deserialized to an array, the values obtained
     * MAY then be injected into the response using this method. This method
     * will typically be invoked by a factory marshaling request parameters.
     *
     * @param array $values The deserialized body parameters, if any.
     * @return void
     * @throws \InvalidArgumentException For $values that cannot be accepted.
     */
    function setBodyParams(array $values) {

        $this->bodyParams = $values;

    }

    /**
     * Random values that the current PSR-7 spec dictates we need.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Retrieve attributes derived from the request.
     *
     * If a router or similar is used to match against the path and/or request,
     * this method MAY be used to retrieve the results, so long as those
     * results can be represented as an array.
     *
     * @return array Attributes derived from the request.
     */
    function getAttributes() {

        return $this->attributes;

    }

    /**
     * Set attributes derived from the request
     *
     * If a router or similar is used to match against the path and/or request,
     * this method MAY be used to inject the request with the results, so long
     * as those results can be represented as an array.
     *
     * @param array $attributes Attributes derived from the request.
     * @return void
     */
    function setAttributes(array $attributes) {

        $this->attributes = $attributes;

    }

    /**
     * Sets the absolute url.
     *
     * @param string $url
     * @return void
     */
    function setAbsoluteUrl($url) {

        $this->absoluteUrl = $url;

    }


    /**
     * Returns the absolute url.
     *
     * @return string
     */
    function getAbsoluteUrl() {

        return $this->absoluteUrl;

    }

    /**
     * Base url
     *
     * @var string
     */
    protected $baseUrl = '/';

    /**
     * Sets a base url.
     *
     * This url is used for relative path calculations.
     *
     * @param string $url
     * @return void
     */
    function setBaseUrl($url) {

        $this->baseUrl = $url;

    }

    /**
     * Returns the current base url.
     *
     * @return string
     */
    function getBaseUrl() {

        return $this->baseUrl;

    }

    /**
     * Returns the relative path.
     *
     * This is being calculated using the base url. This path will not start
     * with a slash, so it will always return something like
     * 'example/path.html'.
     *
     * If the full path is equal to the base url, this method will return an
     * empty string.
     *
     * This method will also urldecode the path, and if the url was incoded as
     * ISO-8859-1, it will convert it to UTF-8.
     *
     * If the path is outside of the base url, a LogicException will be thrown.
     *
     * @return string
     */
    function getPath() {

        // Removing duplicated slashes.
        $uri = str_replace('//','/',$this->getUrl());

        if (strpos($uri,$this->getBaseUrl())===0) {

            // We're not interested in the query part (everything after the ?).
            list($uri) = explode('?', $uri);
            return trim(URLUtil::decodePath(substr($uri,strlen($this->getBaseUrl()))),'/');

        }
        // A special case, if the baseUri was accessed without a trailing
        // slash, we'll accept it as well.
        elseif ($uri.'/' === $this->getBaseUrl()) {

            return '';

        }

        throw new \LogicException('Requested uri (' . $this->getUrl() . ') is out of base uri (' . $this->getBaseUrl() . ')');
    }

    /**
     * An array containing the raw _SERVER array.
     *
     * @var array
     */
    protected $rawServerData;

    /**
     * Returns an item from the _SERVER array.
     *
     * If the value does not exist in the array, null is returned.
     *
     * @param string $valueName
     * @return string|null
     */
    function getRawServerValue($valueName) {

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
    function setRawServerData(array $data) {

        $this->rawServerData = $data;

    }

    /**
     * Serializes the request object as a string.
     *
     * This is useful for debugging purposes.
     *
     * @return string
     */
    function __toString() {

        $out = $this->getMethod() . ' ' . $this->getUrl() . ' HTTP/' . $this->getHTTPVersion() . "\r\n";

        foreach($this->getHeaders() as $key=>$value) {
            foreach($value as $v) {
                if ($key==='Authorization') {
                    list($v) = explode(' ', $v,2);
                    $v  .= ' REDACTED';
                }
                $out .= $key . ": " . $v . "\r\n";
            }
        }
        $out .= "\r\n";
        $out .= $this->getBodyAsString();

        return $out;

    }

}
