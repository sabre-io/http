<?php

namespace Sabre\HTTP;

/**
 * This exception represents a HTTP error coming from the Client.
 *
 * By default the Client will not emit these, this has to be explicitly enabled
 * with the setThrowExceptions method.
 *
 * @copyright Copyright (C) 2009-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class ClientHttpException extends \Exception implements HttpException {

    /**
     * Response object
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Constructor
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response) {

        $this->response = $response;

        list($httpCode, $humanReadable) = explode(' ', $response->getStatus(), 2);
        parent::__construct($humanReadable, $httpCode);

    }

    /**
     * The http status code for the error.
     *
     * This may either be just the number, or a number and a human-readable
     * message, separated by a space.
     *
     * @return string|null
     */
    public function getHttpStatus() {

        return $this->response->getStatus();

    }

    /**
     * Returns the full response object.
     *
     * @return ResponseInterface
     */
    public function getResponse() {

        return $this->response;

    }

}
