<?php

namespace Sabre\HTTP;

/**
 * This should be the basis for any HTTP client.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface ClientInterface {

    /**
     * Basic authentication
     */
    const AUTH_BASIC = 1;

    /**
     * Digest authentication
     */
    const AUTH_DIGEST = 2;

    /**
     * Performs the HTTP request
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function request(RequestInterface $request); 

}
