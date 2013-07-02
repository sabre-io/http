<?php

namespace Sabre\HTTP;

/**
 * This interface represents a HTTP response.
 *
 * @copyright Copyright (C) 2009-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface ResponseInterface extends MessageInterface {


    /**
     * Returns the current HTTP status.
     *
     * This is the status-code as well as the human readable string.
     *
     * @return string
     */
    function getStatus();

    /**
     * Sets the HTTP status code.
     *
     * This can be either the full HTTP status code with human readable string,
     * for example: "403 I can't let you do that, Dave".
     *
     * Or just the code, in which case the appropriate default message will be
     * added.
     *
     * @param string $status
     * @return void
     */
    function setStatus($status);

    /**
     * Sends the HTTP response back to a HTTP client.
     *
     * This calls php's header() function and streams the body to php://output.
     *
     * @return void
     */
    function send();

}
