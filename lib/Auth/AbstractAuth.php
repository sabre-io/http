<?php

declare(strict_types=1);

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * HTTP Authentication base class.
 *
 * This class provides some common functionality for the various base classes.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class AbstractAuth
{
    /**
     * Authentication realm.
     */
    protected string $realm;

    /**
     * Request object.
     */
    protected RequestInterface $request;

    /**
     * Response object.
     */
    protected ResponseInterface $response;

    /**
     * Creates the object.
     */
    public function __construct(string $realm, RequestInterface $request, ResponseInterface $response)
    {
        $this->realm = $realm;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * This method sends the needed HTTP header and status code (401) to force
     * the user to login.
     */
    abstract public function requireLogin(): void;

    /**
     * Returns the HTTP realm.
     */
    public function getRealm(): string
    {
        return $this->realm;
    }
}
