<?php

namespace Sabre\HTTP;

use Psr\Http\Message\IncomingRequestInterface as PsrRequestInterface;

/**
 * Request Decorator
 *
 * This helper class allows you to easily create decorators for the Request
 * object.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class RequestDecorator implements RequestInterface {

    use MessageDecoratorTrait;
    use BC\MessageTrait;
    use BC\RequestTrait;

    /**
     * Constructor.
     *
     * @param PsrRequestInterface $inner
     */
    function __construct(PsrRequestInterface $inner) {

        $this->inner = $inner;

    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    function getMethod() {

        return $this->inner->getMethod();

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

        $this->inner->setMethod($method);

    }

    /**
     * Retrieves the absolute request URL.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return string|object Returns the URL as a string, or an object that
     *    implements the `__toString()` method. The URL must be an absolute URI
     *    as specified in RFC 3986.
     */
    function getUrl() {

        return $this->inner->getUrl();

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

        $this->inner->setUrl($url);

    }

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

        return $this->inner->getCookieParams();

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

        $this->inner->setCookieParams($cookies);

    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's `parse_str()` would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * @return array
     */
    function getQueryParams() {

        return $this->inner->getQueryParams();

    }

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

        return $this->inner->getFileParams();

    }

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

        return $this->inner->getBodyParams();

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

        $this->inner->setBodyParams($values);

    }

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

        return $this->inner->getAttributes();

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

        $this->inner->setAttributes($attributes);

    }

    /**
     * Returns the absolute url.
     *
     * @return string
     */
    function getAbsoluteUrl() {

        return $this->inner->getAbsoluteUrl();

    }

    /**
     * Sets the absolute url.
     *
     * @param string $url
     * @return void
     */
    function setAbsoluteUrl($url) {

        $this->inner->setAbsoluteUrl($url);

    }

    /**
     * Returns the current base url.
     *
     * @return string
     */
    function getBaseUrl() {

        return $this->inner->getBaseUrl();

    }

    /**
     * Sets a base url.
     *
     * This url is used for relative path calculations.
     *
     * The base url should default to /
     *
     * @param string $url
     * @return void
     */
    function setBaseUrl($url) {

        $this->inner->setBaseUrl($url);

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

        return $this->inner->getPath();

    }

    /**
     * Returns an item from the _SERVER array.
     *
     * If the value does not exist in the array, null is returned.
     *
     * @param string $valueName
     * @return string|null
     */
    function getRawServerValue($valueName) {

        return $this->inner->getRawServerValue($valueName);

    }

    /**
     * Sets the _SERVER array.
     *
     * @param array $data
     * @return void
     */
    function setRawServerData(array $data) {

        $this->inner->setRawServerData($data);

    }

    /**
     * Serializes the request object as a string.
     *
     * This is useful for debugging purposes.
     *
     * @return string
     */
    function __toString() {

        return $this->inner->__toString();

    }
}
