<?php

namespace Sabre\HTTP;

/**
 * This is the abstract base class for both the Request and Response objects.
 *
 * This object contains a few simple methods that are shared by both.
 *
 * @copyright Copyright (C) 2009-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Message implements MessageInterface {

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
     * HTTP message version (1.0 or 1.1)
     *
     * @var string
     */
    protected $httpVersion = '1.1';

    /**
     * Returns the message body, as a stream.
     *
     * Note that streams are usually 'read once' and depending on the stream,
     * they can not always be rewinded.
     *
     * If you plan to read the body here, but need it later as well; be
     * prepared to duplicate the stream and set it again.
     *
     * @param bool $asString
     * @return resource|string
     */
    public function getBody($asString = false) {

        if (!$this->body) {
            $this->body = '';
        }

        if ($asString) {
            if (is_string($this->body)) {
                return $this->body;
            } else {
                return stream_get_contents($this->body);
            }
        } else {
            if (is_string($this->body)) {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $this->body);
                rewind($stream);
                return $stream;
            } else {
                return $this->body;
            }
        }

    }

    /**
     * Replaces the body resource with a new stream or string.
     *
     * @param resource $body
     */
    public function setBody($body) {

        $this->body = $body;

    }

    /**
     * Returns all the HTTP headers as an array.
     *
     * @return array
     */
    public function getHeaders() {

        return $this->headers;

    }

    /**
     * Returns a specific HTTP header, based on it's name.
     *
     * The name must be treated as case-insensitive.
     *
     * If the header does not exist, this method must return null.
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader($name) {

        foreach($this->headers as $key=>$value) {
            if (strtolower($key)===strtolower($name)) {
                return $value;
            }
        }

        return null;

    }

    /**
     * Sets a new set of HTTP headers.
     *
     * This method should append the new headers, not wipe out the existing
     * ones.
     *
     * @param array $headers
     * @return void
     */
    public function setHeaders(array $headers) {

        $this->headers = $headers;

    }

    /**
     * Adds a new set of HTTP headers.
     *
     * Any header specified in the array that already exists will be
     * overwritten, but any other existing headers will be retained.
     *
     * @param array $headers
     * @return void
     */
    public function addHeaders(array $headers) {

        $this->headers = array_merge($this->headers, $headers);

    }

    /**
     * Updates a HTTP header.
     *
     * The case-sensitity of the name value must be retained as-is.
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public function setHeader($name, $value) {

        $this->headers[$name] = $value;

    }

    /**
     * Removes a HTTP header.
     *
     * The specified header name must be treated as case-insenstive.
     * This method should return true if the header was successfully deleted,
     * and false if the header did not exist.
     *
     * @return bool
     */
    public function removeHeader($name) {

        foreach($this->headers as $key=>$value) {
            if (strtolower($key)===strtolower($name)) {
                unset($this->headers[$key]);
                return true;
            }
        }
        return false;

    }

    /**
     * Sets the HTTP version.
     *
     * Should be 1.0 or 1.1.
     *
     * @param string $version
     * @return void
     */
    public function setHttpVersion($version) {

        $this->httpVersion = $version;

    }

    /**
     * Returns the HTTP version.
     *
     * @return string
     */
    public function getHttpVersion() {

        return $this->httpVersion;

    }
}
