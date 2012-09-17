<?php

namespace Sabre\HTTP;

/**
 * This class represents the 'server api'.
 *
 * It provides access to the global request information, and provides the only
 * way to send the response back.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sapi {

    /**
     * Returns the PHP request
     *
     * @return Sabre\HTTP\RequestInterface
     */
    public function getRequest() {

        return Response::createFromServerArray(
            $this->getServerArray(),
            fopen('php://input','r')
        );

    }

    /**
     * Sends the HTTP response back to the client.
     *
     * @param Sabre\HTTP\Response $response
     * @return void
     */
    public function sendResponse(Response $response) {

        header('HTTP/1.1 ' . $response->getStatus());
        foreach($response->getHeaders() as $key=>$value) {
            header($key . ': ' . $value);
        }
        fpassthru($response->getBody());

    }

}
