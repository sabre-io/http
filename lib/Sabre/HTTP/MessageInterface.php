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
     * Returns the message body, as a stream
     */
    function getBody();

    /**
     * Updates the message body.
     *
     * @param resource $body
     */
    function setBody($body);

    /**
     * Returns all the HTTP headers as an array.
     * This method must normalize all headers to lowercase.
     *
     * @return array
     */
    function getHeaders();

    /**
     * Returns a specific HTTP header, based on it's name.
     *
     * The name must be treated as case-insensitive.
     *
     * @param string $name
     * @return string
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
     * Removes a HTTP header.
     *
     * The specified header name must be treated as case-insenstive.
     *
     * @return string
     */
    function removeHeader($name);

}
