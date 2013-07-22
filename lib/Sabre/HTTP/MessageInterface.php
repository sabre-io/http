<?php

namespace Sabre\HTTP;

/**
 * The MessageInterface is the base interface that's used by both
 * the RequestInterface and ResponseInterface.
 *
 * @copyright Copyright (C) 2009-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface MessageInterface {

    /**
     * Returns the message body, as a stream.
     *
     * Note that streams are usually 'read once' and depending on the stream,
     * they can not always be rewinded.
     *
     * If you plan to read the body here, but need it later as well; be
     * prepared to duplicate the stream and set it again.
     *
     * @return resource
     */
    function getBody();

    /**
     * Updates the body resource with a new stream.
     *
     * @param resource $body
     * @return void
     */
    function setBody($body);

    /**
     * Returns all the HTTP headers as an array.
     *
     * @return array
     */
    function getHeaders();

    /**
     * Returns a specific HTTP header, based on it's name.
     *
     * The name must be treated as case-insensitive.
     *
     * If the header does not exist, this method must return null.
     *
     * @param string $name
     * @return string|null
     */
    function getHeader($name);

    /**
     * Updates a HTTP header.
     *
     * The case-sensitity of the name value must be retained as-is.
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    function setHeader($name, $value);

    /**
     * Resets HTTP headers
     *
     * This method overwrites all existing HTTP headers
     *
     * @param array $headers
     * @return void
     */
    function setHeaders(array $headers);

    /**
     * Adds a new set of HTTP headers.
     *
     * Any header specified in the array that already exists will be
     * overwritten, but any other existing headers will be retained.
     *
     * @param array $headers
     * @return void
     */
    function addHeaders(array $headers);

    /**
     * Removes a HTTP header.
     *
     * The specified header name must be treated as case-insenstive.
     * This method should return true if the header was successfully deleted,
     * and false if the header did not exist.
     *
     * @return bool
     */
    function removeHeader($name);

    /**
     * Sets the HTTP version.
     *
     * Should be 1.0 or 1.1.
     *
     * @param string $version
     * @return void
     */
    function setHttpVersion($version);

    /**
     * Returns the HTTP version.
     *
     * @return string
     */
    function getHttpVersion();

}
