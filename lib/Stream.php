<?php

namespace Sabre\HTTP;

use InvalidArgumentException;

/**
 * This object represents a body of a HTTP request or response.
 *
 * It effectively just wraps a PHP stream. You may supply either a string or
 * a stream to create a http body.
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Stream implements \Psr\Http\Message\StreamableInterface {

    /**
     * Body stream
     *
     * @var resource
     */
    protected $stream;

    /**
     * Size of the stream.
     *
     * This may not always be available or accurate.
     *
     * @var int
     */
    protected $size;

    /**
     * Creates the body object.
     *
     * You may either supply a string or a stream to construct this object.
     *
     * @param string|resource $stream
     * @param int $size Size of the body in bytes, if known.
     */
    function __construct($stream, $size = null) {

        $this->attach($stream);
        $this->size = $size;

    }
    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * @return string
     */
    function __toString() {

        $this->seek(0);
        return stream_get_contents($this->stream);

    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    function close() {

        fclose($this->stream);

    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    function detach() {

        $stream = $this->stream;
        $this->stream = null;
        return $stream;

    }

    /**
     * Replaces the underlying stream resource with the provided stream.
     *
     * Use this method to replace the underlying stream with another; as an
     * example, in server-side code, if you decide to return a file, you
     * would replace the original content-oriented stream with the file
     * stream.
     *
     * Any internal state such as caching of cursor position should be reset
     * when attach() is called, as the stream has changed.
     *
     * @param string|resource $stream The underlying stream. String values
     *                                SHOULD be used to create a stream
     *                                resource.
     * @return void
     * @throws \InvalidArgumentException For invalid $stream arguments.
     */
    function attach($stream) {

        if (is_string($stream)) {
            $this->size = strlen($stream);
            $h = fopen('php://temp','r+');
            fwrite($h, $stream);
            rewind($h);
            $this->stream = $h;
        } elseif (is_resource($stream)) {
            $this->stream = $stream;
        } else {
            throw new InvalidArgumentException('You must pass a stream or a string');
        }

    }

    /**
     * Get the size of the stream if known
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    function getSize() {

        return $this->size;

    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int|bool Position of the file pointer or false on error.
     */
    function tell() {

        return ftell($this->stream);

    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    function eof() {

        return feof($this->stream);

    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    function isSeekable() {

        return $this->getMetaData('seekable');

    }

    /**
     * Seek to a position in the stream.
     *
     * @link  http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *                    based on the seek offset. Valid values are identical
     *                    to the built-in PHP $whence values for `fseek()`.
     *                    SEEK_SET: Set position equal to offset bytes
     *                    SEEK_CUR: Set position to current location plus offset
     *                    SEEK_END: Set position to end-of-stream plus offset
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    function seek($offset, $whence = SEEK_SET) {

        return fseek($this->stream, $offset, $whence);

    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    function isWritable() {

        $mode = $this->getMetaData('mode');
        return $mode!=='r';

    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     *
     * @return int|bool Returns the number of bytes written to the stream on
     *                  success or FALSE on failure.
     */
    function write($string) {

        return fwrite($this->stream, $string);

    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    function isReadable() {

        $mode = $this->getMetaData('mode');
        return strpos($mode,'r')!==false || strpos($mode, '+')!==false;

    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *                    them. Fewer than $length bytes may be returned if
     *                    underlying stream call returns fewer bytes.
     * @return string|false Returns the data read from the stream, false if
     *                      unable to read or if an error occurs.
     */
    function read($length) {

        return fread($this->stream, $length);

    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     */
    function getContents() {

        return stream_get_contents($this->stream);

    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *                          provided. Returns a specific key value if a key
     *                          is provided and the value is found, or null if
     *                          the key is not found.
     */
    function getMetadata($key = null) {

        if (is_null($key)) {
            return stream_get_meta_data($key);
        } else {
            $info = stream_get_meta_data();
            return isset($info[$key])?$info[$key]:null;
        }

    }

}
