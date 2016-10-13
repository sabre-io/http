<?php declare (strict_types=1);

namespace Sabre\HTTP;

/**
 * The RequestInterface represents a HTTP request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface RequestInterface extends MessageInterface {

    /**
     * Returns the current HTTP method
     */
    function getMethod() : string;

    /**
     * Sets the HTTP method
     *
     * @return void
     */
    function setMethod(string $method);

    /**
     * Returns the request url.
     */
    function getUrl() : string;

    /**
     * Sets the request url.
     *
     * @return void
     */
    function setUrl(string $url);

    /**
     * Returns the absolute url.
     */
    function getAbsoluteUrl() : string;

    /**
     * Sets the absolute url.
     *
     * @return void
     */
    function setAbsoluteUrl(string $url);

    /**
     * Returns the current base url.
     */
    function getBaseUrl() : string;

    /**
     * Sets a base url.
     *
     * This url is used for relative path calculations.
     *
     * The base url should default to /
     *
     * @return void
     */
    function setBaseUrl(string $url);

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
     */
    function getPath() : string;

    /**
     * Returns the list of query parameters.
     *
     * This is equivalent to PHP's $_GET superglobal.
     */
    function getQueryParameters() : array;

    /**
     * Returns the POST data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     */
    function getPostData() : array;

    /**
     * Sets the post data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     *
     * This would not have been needed, if POST data was accessible as
     * php://input, but unfortunately we need to special case it.
     *
     * @return void
     */
    function setPostData(array $postData);

    /**
     * Returns an item from the _SERVER array.
     *
     * If the value does not exist in the array, null is returned.
     *
     * @return string|null
     */
    function getRawServerValue(string $valueName);

    /**
     * Sets the _SERVER array.
     *
     * @return void
     */
    function setRawServerData(array $data);


}
