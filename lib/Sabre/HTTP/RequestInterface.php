<?php

namespace Sabre\HTTP;

/**
 * The RequestInterface represents a HTTP request.
 *
 * @copyright Copyright (C) 2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface RequestInterface extends MessageInterface {

    /**
     * Returns the current HTTP method
     *
     * @return string
     */
    function getMethod();

    /**
     * Sets the HTTP method
     *
     * @param string $method
     * @return void
     */
    function setMethod($method);

    /**
     * Returns the request url.
     *
     * @return string
     */
    function getUrl();

    /**
     * Sets the request url.
     *
     * @param string $url
     * @return void
     */
    function setUrl($url);

}
