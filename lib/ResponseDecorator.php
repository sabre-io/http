<?php

namespace Sabre\HTTP;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

/**
 * Response Decorator
 *
 * This helper class allows you to easily create decorators for the Response
 * object.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ResponseDecorator implements ResponseInterface {

    use MessageDecoratorTrait;
    use BC\MessageTrait;
    use BC\ResponseTrait;

    /**
     * Constructor.
     *
     * @param ResponseInterface $inner
     */
    function __construct(PsrResponseInterface $inner) {

        $this->inner = $inner;

    }

    /**
     * Gets the response Status-Code.
     *
     * The Status-Code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return integer Status code.
     */
    function getStatusCode() {

        return $this->inner->getStatusCode();

    }

    /**
     * Sets the status code of this response.
     *
     * @param integer $code The 3-digit integer result code to set.
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    function setStatusCode($code) {

        $this->inner->setStatusCode($code);

    }

    /**
     * Gets the response Reason-Phrase, a short textual description of the Status-Code.
     *
     * Because a Reason-Phrase is not a required element in a response
     * Status-Line, the Reason-Phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * Status-Code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string|null Reason phrase, or null if unknown.
     */
    function getReasonPhrase() {

        return $this->inner->getReasonPhrase();

    }

    /**
     * Sets the Reason-Phrase of the response.
     *
     * If no Reason-Phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * Status-Code.
     *
     * @param string $phrase The Reason-Phrase to set.
     * @throws \InvalidArgumentException For non-string $phrase arguments.
     */
    function setReasonPhrase($phrase) {

        $this->inner->setReasonPhrase($phrase);

    }

    /**
     * Serializes the request object as a string.
     *
     * This is useful for debugging purposes.
     *
     * @return string
     */
    function __toString() {

        return $this->inner->__toString();

    }
}
