<?php

namespace Sabre\HTTP;

use Psr\Http\Message\StreamableInterface;

/**
 * This is the abstract base class for both the Request and Response objects.
 *
 * This object contains a few simple methods that are shared by both.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class Message implements MessageInterface {

    use BC\MessageTrait;

    /**
     * Request body
     *
     * This should be a stream resource
     *
     * @var resource
     */
    protected $body;

    /**
     * Contains the list of HTTP headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * HTTP protocol version (1.0 or 1.1)
     *
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * Gets the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    function getProtocolVersion() {

        return $this->protocolVersion;

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

        $this->protocolVersion = $version;

    }

    /**
     * Gets the body of the message.
     *
     * @return StreamableInterface|null Returns the body, or null if not set.
     */
    function getBody() {

        return $this->body;

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

        $this->body = $body;

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
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings.
     */
    function getHeaders() {

        $result = [];
        foreach($this->headers as $headerInfo) {
            $result[$headerInfo[0]] = $headerInfo[1];
        }
        return $result;

    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    function hasHeader($name) {

        return isset($this->headers[strtolower($name)]);

    }

    /**
     * Returns a specific HTTP header, based on it's name.
     *
     * The name must be treated as case-insensitive.
     * If the header does not exist, this method must return null.
     *
     * If a header appeared more than once in a HTTP request, this method will
     * concatenate all the values with a comma.
     *
     * @param string $header Case-insensitive header name.
     * @return string[]
     */
    function getHeader($name) {

        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            return implode(',', $this->headers[$name][1]);
        }
        return null;

    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * If the header did not exists, this method will return an empty array.
     *
     * @param string $header Case-insensitive header name.
     * @return string[]
     */
    function getHeaderAsArray($name) {

        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            return $this->headers[$name][1];
        }

        return [];

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
    function setHeader($name, $value) {

        $this->headers[strtolower($name)] = [$name, (array)$value];

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
    function addHeader($name, $value) {

        $lName = strtolower($name);
        if (isset($this->headers[$lName])) {
            $this->headers[$lName][1] = array_merge(
                $this->headers[$lName][1],
                (array)$value
            );
        } else {
            $this->headers[$lName] = [
                $name,
                (array)$value
            ];
        }

    }

    /**
     * Remove a specific header by case-insensitive name.
     *
     * @param string $header HTTP header to remove
     * @return void
     */
    function removeHeader($name) {

        $name = strtolower($name);
        if (!isset($this->headers[$name])) {
            return false;
        }
        unset($this->headers[$name]);
        return true;

    }

}
