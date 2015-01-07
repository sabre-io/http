<?php

namespace Sabre\HTTP;

use Psr\Http\Message\IncomingRequestInterface as PsrRequestInterface;

/**
 * The RequestInterface represents a HTTP request.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface RequestInterface extends PsrRequestInterface, MessageInterface {

    /**
     * Returns the absolute url.
     *
     * @return string
     */
    function getAbsoluteUrl();

    /**
     * Sets the absolute url.
     *
     * @param string $url
     * @return void
     */
    function setAbsoluteUrl($url);

    /**
     * Returns the current base url.
     *
     * @return string
     */
    function getBaseUrl();

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
    function setBaseUrl($url);

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
    function getPath();

    /**
     * Returns an item from the _SERVER array.
     *
     * If the value does not exist in the array, null is returned.
     *
     * @param string $valueName
     * @return string|null
     */
    function getRawServerValue($valueName);

    /**
     * Sets the _SERVER array.
     *
     * @param array $data
     * @return void
     */
    function setRawServerData(array $data);

}
