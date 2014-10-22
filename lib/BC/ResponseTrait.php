<?php

namespace Sabre\HTTP\BC;

use Sabre\HTTP\Response;

/**
 * This trait adds backwards-compatiblity features for Response objects.
 *
 * @copyright Copyright (C) 2009-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
trait ResponseTrait {

    /**
     * Returns the human-readable status string.
     *
     * In the case of a 200, this may for example be 'OK'.
     *
     * @deprecated Use getReasonPhrase instead.
     * @return string
     */
    function getStatusText() {

        return $this->getReasonPhrase();

    }

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
    function setStatus($status) {

        if (ctype_digit($status) || is_int($status)) {

            $statusCode = $status;
            $statusText = isset(Response::$statusCodes[$status])?Response::$statusCodes[$status]:'Unknown';

        } else {
            list(
                $statusCode,
                $statusText
            ) = explode(' ', $status, 2);
        }
        if ($statusCode < 100 || $statusCode > 999) {
            throw new \InvalidArgumentException('The HTTP status code must be exactly 3 digits');
        }

        $this->setStatusCode($statusCode);
        $this->setReasonPhrase($statusText);

    }

    /**
     * Returns the current HTTP status code.
     *
     * @deprecated use getStatusCode instead.
     * @return int
     */
    function getStatus() {

        return $this->getStatusCode();

    }

}
