<?php

namespace Sabre\HTTP;

/**
 * This interface represents a HTTP response.
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

}
