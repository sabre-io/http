<?php

namespace Sabre\HTTP;

use Sabre\URI;

/**
 * URL utility class
 *
 * This class provides methods to deal with encoding and decoding url (percent encoded) strings.
 *
 * It was not possible to use PHP's built-in methods for this, because some clients don't like
 * encoding of certain characters.
 *
 * Specifically, it was found that GVFS (gnome's webdav client) does not like encoding of ( and
 * ). Since these are reserved, but don't have a reserved meaning in url, these characters are
 * kept as-is.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class URLUtil {

    /**
     * Encodes the path of a url.
     *
     * slashes (/) are treated as path-separators.
     *
     * @param string $path
     * @return string
     */
    static function encodePath($path) {

        return preg_replace_callback('/([^A-Za-z0-9_\-\.~\(\)\/:@])/', function($match) {

            return '%' . sprintf('%02x', ord($match[0]));

        }, $path);

    }

    /**
     * Encodes a 1 segment of a path
     *
     * Slashes are considered part of the name, and are encoded as %2f
     *
     * @param string $pathSegment
     * @return string
     */
    static function encodePathSegment($pathSegment) {

        return preg_replace_callback('/([^A-Za-z0-9_\-\.~\(\):@])/', function($match) {

            return '%' . sprintf('%02x', ord($match[0]));

        }, $pathSegment);
    }

    /**
     * Decodes a url-encoded path
     *
     * @param string $path
     * @return string
     */
    static function decodePath($path) {

        return self::decodePathSegment($path);

    }

    /**
     * Decodes a url-encoded path segment
     *
     * @param string $path
     * @return string
     */
    static function decodePathSegment($path) {

        $path = rawurldecode($path);
        $encoding = mb_detect_encoding($path, ['UTF-8', 'ISO-8859-1']);

        switch ($encoding) {

            case 'ISO-8859-1' :
                $path = utf8_encode($path);

        }

        return $path;

    }

    /**
     * Returns the 'dirname' and 'basename' for a path.
     *
     * @deprecated Use Sabre\Uri\split().
     * @param string $path
     * @return array
     */
    static function splitPath($path) {

        return Uri\split($path);

    }

    /**
     * Resolves relative urls, like a browser would.
     *
     * @deprecated Use Sabre\Uri\resolve().
     * @param string $basePath
     * @param string $newPath
     * @return string
     */
    static function resolve($basePath, $newPath) {

        return Uri\resolve($basePath, $newPath);

    }

}
