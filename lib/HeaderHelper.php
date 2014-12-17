<?php

namespace Sabre\HTTP;

use DateTime;

/**
 * A collection of useful helpers for parsing or generating various HTTP
 * headers.
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HeaderHelper {

    /**
     * Parses a HTTP date-string.
     *
     * This method returns false if the date is invalid.
     *
     * The following formats are supported:
     *    Sun, 06 Nov 1994 08:49:37 GMT    ; IMF-fixdate
     *    Sunday, 06-Nov-94 08:49:37 GMT   ; obsolete RFC 850 format
     *    Sun Nov  6 08:49:37 1994         ; ANSI C's asctime() format
     *
     * See:
     *   http://tools.ietf.org/html/rfc7231#section-7.1.1.1
     *
     * @param string $dateString
     * @return bool|DateTime
     */
    static function parseDate($dateString) {

        // Only the format is checked, valid ranges are checked by strtotime below
        $month = '(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)';
        $weekday = '(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)';
        $wkday = '(Mon|Tue|Wed|Thu|Fri|Sat|Sun)';
        $time = '([0-1]\d|2[0-3])(\:[0-5]\d){2}';
        $date3 = $month . ' ([12]\d|3[01]| [1-9])';
        $date2 = '(0[1-9]|[12]\d|3[01])\-' . $month . '\-\d{2}';
        // 4-digit year cannot begin with 0 - unix timestamp begins in 1970
        $date1 = '(0[1-9]|[12]\d|3[01]) ' . $month . ' [1-9]\d{3}';

        // ANSI C's asctime() format
        // 4-digit year cannot begin with 0 - unix timestamp begins in 1970
        $asctime_date = $wkday . ' ' . $date3 . ' ' . $time . ' [1-9]\d{3}';
        // RFC 850, obsoleted by RFC 1036
        $rfc850_date = $weekday . ', ' . $date2 . ' ' . $time . ' GMT';
        // RFC 822, updated by RFC 1123
        $rfc1123_date = $wkday . ', ' . $date1 . ' ' . $time . ' GMT';
        // allowed date formats by RFC 2616
        $HTTP_date = "($rfc1123_date|$rfc850_date|$asctime_date)";

        // allow for space around the string and strip it
        $dateString = trim($dateString, ' ');
        if (!preg_match('/^' . $HTTP_date . '$/', $dateString))
            return false;

        // append implicit GMT timezone to ANSI C time format
        if (strpos($dateString, ' GMT') === false)
            $dateString .= ' GMT';

        try {
            return new DateTime($dateString, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * Transforms a DateTime object to a valid HTTP/1.1 Date header value
     *
     * @param DateTime $dateTime
     * @return string
     */
    static function toDate(DateTime $dateTime) {

        // We need to clone it, as we don't want to affect the existing
        // DateTime.
        $dateTime = clone $dateTime;
        $dateTime->setTimeZone(new \DateTimeZone('GMT'));
        return $dateTime->format('D, d M Y H:i:s \G\M\T');

    }

    /**
     * Parses the Prefer header, as defined in RFC7240.
     *
     * Input can be given as a single header value (string) or multiple headers
     * (array of string).
     *
     * This method will return a key->value array with the various Prefer
     * parameters.
     *
     * Prefer: return=minimal will result in:
     *
     * [ 'return' => 'minimal' ]
     *
     * Prefer: foo, wait=10 will result in:
     *
     * [ 'foo' => true, 'wait' => '10']
     *
     * This method also supports the formats from older drafts of RFC7240, and
     * it will automatically map them to the new values, as the older values
     * are still pretty common.
     *
     * Parameters are currently discarded. There's no known prefer value that
     * uses them.
     *
     * @param string|string[] $header
     * @return array
     */
    static function parsePrefer($input) {

        $token = '[!#$%&\'*+\-.^_`~A-Za-z0-9]+';

        // Work in progress
        $word = '(?: [a-zA-Z0-9]+ | "[a-zA-Z0-9]*" )';

        $regex = <<<REGEX
/
   ^
   (?<name> $token)      # Prefer property name
   \s*                   # Optional space
   (?: = \s*             # Prefer property value
       (?<value> $word)
   )?
   (?: \s* ; (?: .*))?   # Prefer parameters (ignored)
   $
/x
REGEX;

        $output = [];
        foreach(self::getHeaderValues($input) as $value) {

            if (!preg_match($regex, $value, $matches)) {
                // Ignore
                continue;
            }

            // Mapping old values to their new counterparts
            switch($matches['name']) {
                case 'return-asynch' :
                    $output['respond-async'] = true;
                    break;
                case 'return-representation' :
                    $output['return'] = 'representation';
                    break;
                case 'return-minimal' :
                    $output['return'] = 'minimal';
                    break;
                case 'strict' :
                    $output['handling'] = 'strict';
                    break;
                case 'lenient' :
                    $output['handling'] = 'lenient';
                    break;
                default :
                    if (isset($matches['value'])) {
                        $value = trim($matches['value'],'"');
                    } else {
                        $value = true;
                    }
                    $output[strtolower($matches['name'])] = empty($value) ? true : $value;
                    break;
            }

        }

        return $output;

    }

    /**
     * This method splits up headers into all their individual values.
     *
     * A HTTP header may have more than one header, such as this:
     *   Cache-Control: private, no-store
     *
     * Header values are always split with a comma.
     *
     * You can pass either a string, or an array. The resulting value is always
     * an array with each spliced value.
     *
     * If the second headers argument is set, this value will simply be merged
     * in. This makes it quicker to merge an old list of values with a new set.
     *
     * @param string|string[] $values
     * @param string|string[] $values2
     * @return string[]
     */
    static function getHeaderValues($values, $values2 = null) {

        $values = (array)$values;
        if ($values2) {
            $values = array_merge($values, (array)$values2);
        }
        foreach($values as $l1) {
            foreach(explode(',', $l1) as $l2) {
                $result[] = trim($l2);
            }
        }
        return $result;

    }

}
