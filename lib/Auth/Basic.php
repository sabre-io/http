<?php

declare(strict_types=1);

namespace Sabre\HTTP\Auth;

/**
 * HTTP Basic authentication utility.
 *
 * This class helps you set up basic auth. The process is fairly simple:
 *
 * 1. Instantiate the class.
 * 2. Call getCredentials (this will return null or a user/pass pair)
 * 3. If you didn't get valid credentials, call 'requireLogin'
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Basic extends AbstractAuth
{
    /**
     * This method returns a numeric array with a username and password as the
     * only elements.
     *
     * If no credentials were found, this method returns null.
     *
     * @return array<int, string>|null
     */
    public function getCredentials(): ?array
    {
        $auth = $this->request->getHeader('Authorization');

        if (null === $auth) {
            return null;
        }

        if ('basic ' !== strtolower(substr($auth, 0, 6))) {
            return null;
        }

        $decodedAuth = base64_decode(substr($auth, 6), true);

        if (false === $decodedAuth) {
            return null;
        }

        $credentials = explode(':', $decodedAuth, 2);

        if (2 !== count($credentials)) {
            return null;
        }

        return $credentials;
    }

    /**
     * This method sends the needed HTTP header and status code (401) to force
     * the user to login.
     */
    public function requireLogin(): void
    {
        $this->response->addHeader('WWW-Authenticate', 'Basic realm="'.$this->realm.'", charset="UTF-8"');
        $this->response->setStatus(401);
    }
}
