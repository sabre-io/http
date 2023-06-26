<?php

declare(strict_types=1);

namespace Sabre\HTTP;

/**
 * This is the abstract base class for both the Request and Response objects.
 *
 * This object contains a few simple methods that are shared by both.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class Message implements MessageInterface
{
    /**
     * Request body.
     *
     * This should be a stream resource, string or a callback writing the body to php://output
     *
     * @var resource|string|callable|null
     */
    protected $body;

    /**
     * Contains the list of HTTP headers.
     *
     * @var array<string, mixed>
     */
    protected array $headers = [];

    /**
     * HTTP message version (1.0, 1.1 or 2.0).
     */
    protected string $httpVersion = '1.1';

    /**
     * Returns the body as a readable stream resource.
     *
     * Note that the stream may not be rewindable, and therefore may only be
     * read once.
     *
     * @return resource
     */
    public function getBodyAsStream()
    {
        $body = $this->getBody();
        if (is_callable($this->body)) {
            $body = $this->getBodyAsString();
        }
        if (is_string($body) || null === $body) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, (string) $body);
            rewind($stream);

            return $stream;
        }

        return $body;
    }

    /**
     * Returns the body as a string.
     *
     * Note that because the underlying data may be based on a stream, this
     * method could only work correctly the first time.
     */
    public function getBodyAsString(): string
    {
        $body = $this->getBody();
        if (is_string($body)) {
            return $body;
        }
        if (null === $body) {
            return '';
        }
        if (is_callable($body)) {
            ob_start();
            $body();

            return ob_get_clean();
        }
        /**
         * @var string|int|null $contentLength
         */
        $contentLength = $this->getHeader('Content-Length');
        if (null !== $contentLength && (is_int($contentLength) || ctype_digit($contentLength))) {
            return stream_get_contents($body, (int) $contentLength);
        }

        return stream_get_contents($body);
    }

    /**
     * Returns the message body, as its internal representation.
     *
     * This could be either a string, a stream or a callback writing the body to php://output.
     *
     * @return resource|string|callable|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Replaces the body resource with a new stream, string or a callback writing the body to php://output.
     *
     * @param resource|string|callable $body
     */
    public function setBody($body): void
    {
        $this->body = $body;
    }

    /**
     * Returns all the HTTP headers as an array.
     *
     * Every header is returned as an array, with one or more values.
     *
     * @return array<string, mixed>
     */
    public function getHeaders(): array
    {
        $result = [];
        foreach ($this->headers as $headerInfo) {
            $result[$headerInfo[0]] = $headerInfo[1];
        }

        return $result;
    }

    /**
     * Will return true or false, depending on if an HTTP header exists.
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * Returns a specific HTTP header, based on its name.
     *
     * The name must be treated as case-insensitive.
     * If the header does not exist, this method must return null.
     *
     * If a header appeared more than once in an HTTP request, this method will
     * concatenate all the values with a comma.
     *
     * Note that this not make sense for all headers. Some, such as
     * `Set-Cookie` cannot be logically combined with a comma. In those cases
     * you *should* use getHeaderAsArray().
     */
    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            return implode(',', $this->headers[$name][1]);
        }

        return null;
    }

    /**
     * Returns an HTTP header as an array.
     *
     * For every time the HTTP header appeared in the request or response, an
     * item will appear in the array.
     *
     * If the header did not exist, this method will return an empty array.
     *
     * @return string[]
     */
    public function getHeaderAsArray(string $name): array
    {
        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            return $this->headers[$name][1];
        }

        return [];
    }

    /**
     * Updates an HTTP header.
     *
     * The case-sensitivity of the name value must be retained as-is.
     *
     * If the header already existed, it will be overwritten.
     *
     * @param string|string[] $value
     */
    public function setHeader(string $name, $value): void
    {
        $this->headers[strtolower($name)] = [$name, (array) $value];
    }

    /**
     * Sets a new set of HTTP headers.
     *
     * The headers array should contain headernames for keys, and their value
     * should be specified as either a string or an array.
     *
     * Any header that already existed will be overwritten.
     *
     * @param array<string, mixed> $headers
     */
    public function setHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
    }

    /**
     * Adds an HTTP header.
     *
     * This method will not overwrite any existing HTTP header, but instead add
     * another value. Individual values can be retrieved with
     * getHeadersAsArray.
     *
     * @param mixed|mixed[] $value
     */
    public function addHeader(string $name, $value): void
    {
        $lName = strtolower($name);
        if (isset($this->headers[$lName])) {
            $this->headers[$lName][1] = array_merge(
                $this->headers[$lName][1],
                (array) $value
            );
        } else {
            $this->headers[$lName] = [
                $name,
                (array) $value,
            ];
        }
    }

    /**
     * Adds a new set of HTTP headers.
     *
     * Any existing headers will not be overwritten.
     *
     * @param array<string, mixed> $headers
     */
    public function addHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }
    }

    /**
     * Removes an HTTP header.
     *
     * The specified header name must be treated as case-insensitive.
     * This method should return true if the header was successfully deleted,
     * and false if the header did not exist.
     */
    public function removeHeader(string $name): bool
    {
        $name = strtolower($name);
        if (!isset($this->headers[$name])) {
            return false;
        }
        unset($this->headers[$name]);

        return true;
    }

    /**
     * Sets the HTTP version.
     *
     * Should be 1.0, 1.1 or 2.0.
     */
    public function setHttpVersion(string $version): void
    {
        $this->httpVersion = $version;
    }

    /**
     * Returns the HTTP version.
     */
    public function getHttpVersion(): string
    {
        return $this->httpVersion;
    }
}
