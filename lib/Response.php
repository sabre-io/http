<?php

namespace Sabre\HTTP;

/**
 * This class represents a single HTTP response.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Response extends Message implements ResponseInterface {

    use BC\ResponseTrait;

    /**
     * This is the list of currently registered HTTP status codes.
     *
     * @var array
     */
    static $statusCodes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authorative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status', // RFC 4918
        208 => 'Already Reported', // RFC 5842
        226 => 'IM Used', // RFC 3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot', // RFC 2324
        422 => 'Unprocessable Entity', // RFC 4918
        423 => 'Locked', // RFC 4918
        424 => 'Failed Dependency', // RFC 4918
        426 => 'Upgrade Required',
        428 => 'Precondition Required', // RFC 6585
        429 => 'Too Many Requests', // RFC 6585
        431 => 'Request Header Fields Too Large', // RFC 6585
        451 => 'Unavailable For Legal Reasons', // draft-tbray-http-legally-restricted-status
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage', // RFC 4918
        508 => 'Loop Detected', // RFC 5842
        509 => 'Bandwidth Limit Exceeded', // non-standard
        510 => 'Not extended',
        511 => 'Network Authentication Required', // RFC 6585
    ];

    /**
     * Creates the response object
     *
     * @param string|int $status
     * @param array $headers
     * @param resource $body
     * @return void
     */
    function __construct($status = null, array $headers = null, $body = null) {

        if (!is_null($status)) $this->setStatus($status);
        if (!is_null($headers)) $this->setHeaders($headers);
        if (!is_null($body)) $this->setBody($body);

    }

    /**
     * HTTP status code
     *
     * @var int
     */
    protected $statusCode;

    /**
     * Gets the response Status-Code.
     *
     * The Status-Code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return integer Status code.
     */
    function getStatusCode() {

        return $this->statusCode;

    }

    /**
     * Sets the status code of this response.
     *
     * @param integer $code The 3-digit integer result code to set.
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    function setStatusCode($code) {

        $this->statusCode = $code;

    }

    /**
     * HTTP status text
     *
     * @var string
     */
    protected $reasonPhrase;

    /**
     * Gets the response Reason-Phrase, a short textual description of the
     * Status-Code.
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

        return $this->reasonPhrase;

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

        $this->reasonPhrase = $phrase;

    }

    /**
     * Serializes the response object as a string.
     *
     * This is useful for debugging purposes.
     *
     * @return string
     */
    function __toString() {

        $str = 'HTTP/' . $this->getProtocolVersion() . ' ' . $this->getStatus() . ' ' . $this->getStatusText() . "\r\n";
        foreach($this->getHeaders() as $key=>$value) {
            foreach($value as $v) {
                $str.= $key . ": " . $v . "\r\n";
            }
        }
        $str.="\r\n";
        $str.=$this->getBodyAsString();
        return $str;

    }

}
