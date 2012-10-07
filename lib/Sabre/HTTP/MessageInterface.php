<?php

namespace Sabre\HTTP;

/**
 * The MessageInterface is the base interface that's used by both
 * the RequestInterface and ResponseInterface.
 *
 * @copyright Copyright (C) 2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface MessageInterface {

    /**
     * This method appends a string or stream to the body.
     *
     * @return void
     */
    function sendBody($body);

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
     * Sets a new set of HTTP headers.
     *
     * This method should append the new headers, not wipe out the existing
     * ones.
     *
     * @param array $headers
     * @return void
     */
    function setHeaders(array $headers);

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

}
