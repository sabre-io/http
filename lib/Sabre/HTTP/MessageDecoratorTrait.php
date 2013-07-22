<?php

namespace Sabre\HTTP;

/**
 * This trait contains a bunch of methods, shared by both the RequestDecorator
 * and the ResponseDecorator.
 *
 * Didn't seem needed to create a full class for this, so we're just
 * implementing it as a trait.
 *
 * @copyright Copyright (C) 2009-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
trait MessageDecoratorTrait {

    /**
     * The inner request object.
     *
     * All method calls will be forwarded here.
     *
     * @var MessageInterface
     */
    protected $inner;

    /**
     * Returns the message body, as a stream.
     *
     * Note that streams are usually 'read once' and depending on the stream,
     * they can not always be rewinded.
     *
     * If you plan to read the body here, but need it later as well; be
     * prepared to duplicate the stream and set it again.
     *
     * @param int $returnType
     * @return resource|string
     */
    public function getBody($returnType = self::BODY_STREAM) {

        return $this->inner->getBody($returnType);

    }

    /**
     * Updates the body resource with a new stream.
     *
     * @param resource $body
     * @return void
     */
    public function setBody($body) {

        $this->inner->setBody($body);

    }

    /**
     * Returns all the HTTP headers as an array.
     *
     * @return array
     */
    public function getHeaders() {

        return $this->inner->getHeaders();

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

        return $this->inner->getHeader($name);

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

        $this->inner->setHeader($name, $value);

    }

    /**
     * Resets HTTP headers
     *
     * This method overwrites all existing HTTP headers
     *
     * @param array $headers
     * @return void
     */
    public function setHeaders(array $headers) {

        $this->inner->setHeaders($headers);

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

        $this->inner->addHeaders($headers);

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

        $this->inner->removeHeader($name);

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

        $this->inner->setHttpVersion($version);

    }

    /**
     * Returns the HTTP version.
     *
     * @return string
     */
    public function getHttpVersion() {

        return $this->inner->getHttpVersion();

    }

}
