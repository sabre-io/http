<?php

declare(strict_types=1);

namespace Sabre\HTTP;

/**
 * This exception represents an HTTP error coming from the Client.
 *
 * By default, the Client will not emit these, this has to be explicitly enabled
 * with the setThrowExceptions method.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ClientHttpException extends \Exception implements HttpException
{
    /**
     * Response object.
     */
    protected ResponseInterface $response;

    /**
     * Constructor.
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
        parent::__construct($response->getStatusText(), $response->getStatus());
    }

    /**
     * The http status code for the error.
     */
    public function getHttpStatus(): string
    {
        return (string) $this->response->getStatus();
    }

    /**
     * Returns the full response object.
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
