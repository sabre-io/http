<?php

namespace Sabre\HTTP;

/**
 * This interface represents a HTTP response.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface ResponseInterface extends \Psr\Http\Message\ResponseInterface, MessageInterface {

    /**
     * Sets the HTTP status code.
     *
     * This can be either the full HTTP status code with human readable string,
     * for example: "403 I can't let you do that, Dave".
     *
     * Or just the code, in which case the appropriate default message will be
     * added.
     *
     * @param string|int $status
     * @throws \InvalidArgumentExeption
     * @return void
     */
    function setStatus($status);

    /**
     * Returns the current HTTP status code.
     *
     * @deprecated use getStatusCode instead.
     * @return int
     */
    function getStatus();

    /**
     * Returns the human-readable status string.
     *
     * In the case of a 200, this may for example be 'OK'.
     *
     * @deprecated Use setReasonPhrase instead.
     * @return string
     */
    function getStatusText();

}
