<?php

namespace Sabre\HTTP;

use Psr\Http\Message\StreamableInterface;
use Psr\Http\Message\MessageInterface as PsrMessageInterface;

/**
 * This trait contains a bunch of methods, shared by both the RequestDecorator
 * and the ResponseDecorator.
 *
 * Didn't seem needed to create a full class for this, so we're just
 * implementing it as a trait.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
trait MessageDecoratorTrait {

    /**
     * The inner request object.
     *
     * All method calls will be forwarded here.
     *
     * @var PsrMessageInterface
     */
    protected $inner;

    /**
     * Gets the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    function getProtocolVersion() {

        return $this->inner->getProtocolVersion();

    }

    /**
     * Set the HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * @param string $version HTTP protocol version
     * @return void
     */
    function setProtocolVersion($version) {

        $this->inner->setProtocolVersion($version);

    }

    /**
     * Gets the body of the message.
     *
     * @return StreamableInterface|null Returns the body, or null if not set.
     */
    function getBody() {

        return $this->inner->getBody();

    }

    /**
     * Sets the body of the message.
     *
     * The body MUST be a StreamableInterface object. Setting the body to null MUST
     * remove the existing body.
     *
     * @param StreamableInterface|null $body Body.
     * @return void
     * @throws \InvalidArgumentException When the body is not valid.
     */
    function setBody(StreamableInterface $body = null) {

        $this->inner->setBody($body);

    }

    /**
     * Gets all message headers.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings.
     */
    function getHeaders() {

        return $this->inner->getHeaders();

    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    function hasHeader($header) {

        return $this->inner->hasHeader($header);

    }

    /**
     * Retrieve a header by the given case-insensitive name as a string.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * @param string $header Case-insensitive header name.
     * @return string
     */
    function getHeader($header) {

        return $this->inner->getHeader($header);

    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * If the header did not exists, this method will return an empty array.
     *
     * @param string $name
     * @return string[]
     */
    function getHeaderAsArray($header) {

        return $this->inner->getHeaderAsArray($header);

    }

    /**
     * Sets a header, replacing any existing values of any headers with the
     * same case-insensitive name.
     *
     * The header name is case-insensitive. The header values MUST be a string
     * or an array of strings.
     *
     * @param string $header Header name
     * @param string|string[] $value Header value(s).
     * @return void
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    function setHeader($header, $value) {

        $this->inner->setHeader($header, $value);

    }

    /**
     * Appends a header value for the specified header.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list.
     *
     * @param string $header Header name to add
     * @param string|string[] $value Header value(s).
     * @return void
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    function addHeader($header, $value) {

        $this->inner->addHeader($header, $value);

    }

    /**
     * Remove a specific header by case-insensitive name.
     *
     * @param string $header HTTP header to remove
     * @return void
     */
    function removeHeader($header) {

        $this->inner->removeHeader($header);

    }

}
