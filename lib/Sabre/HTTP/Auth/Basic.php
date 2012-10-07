<?php

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

/**
 * HTTP Basic authentication utility.
 *
 * This class helps you setup basic auth. The process is fairly simple:
 *
 * 1. Instantiate the class.
 * 2. Call getCredentials (this will return null or a user/pass pair)
 * 3. If you didn't get valid credentials, call 'requireLogin'
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Basic {

    /**
     * Authentication realm
     *
     * @var string
     */
    protected $realm;

    /**
     * Creates the basic auth helper.
     *
     * @param string $realm
     * @return void
     */
    public function __construct($realm = 'SabreTooth') {

        $this->realm = $realm;

    }

    /**
     * This method returns a numeric array with a username and password as the
     * only elements.
     *
     * If no credentials were found, this method returns null.
     *
     * @param Sabre\HTTP\Request $request
     * @return null|array
     */
    public function getCredentials(Request $request) {

        $auth = $request->getHeader('Authorization');

        if (!$auth) {
            return null;
        }

        if (strtolower(substr($auth,0,6))!=='basic ') {
            return null;
        }

        return explode(':',base64_decode(substr($auth, 6)), 2);

    }

    /**
     * This method sends the needed HTTP header and statuscode (401) to force
     * the user to login.
     *
     * @param Sabre\HTTP\Response
     * @return void
     */
    public function requireLogin(Response $response) {

        $response->setHeader('WWW-Authenticate','Basic realm="' . $this->realm . '"');
        $response->setStatus(401);

    }

}
