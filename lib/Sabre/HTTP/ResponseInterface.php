<?php

namespace Sabre\HTTP;

/**
 * This method represents a HTTP response.
 *
 * @copyright Copyright (C) 2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
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
     * This must be both the code, as well as the human readable string, for
     * example: "403 I can't let you do that, Dave"
     *
     * @param string $status
     * @return void
     */
    function setStatus($status);

}
