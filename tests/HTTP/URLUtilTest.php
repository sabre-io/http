<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class URLUtilTest extends \PHPUnit\Framework\TestCase
{
    public function testEncodePath(): void
    {
        $str = '';
        for ($i = 0; $i < 128; ++$i) {
            $str .= chr($i);
        }

        $newStr = encodePath($str);

        self::assertEquals(
            '%00%01%02%03%04%05%06%07%08%09%0a%0b%0c%0d%0e%0f'.
            '%10%11%12%13%14%15%16%17%18%19%1a%1b%1c%1d%1e%1f'.
            '%20%21%22%23%24%25%26%27()%2a%2b%2c-./'.
            '0123456789:%3b%3c%3d%3e%3f'.
            '@ABCDEFGHIJKLMNO'.
            'PQRSTUVWXYZ%5b%5c%5d%5e_'.
            '%60abcdefghijklmno'.
            'pqrstuvwxyz%7b%7c%7d~%7f',
            $newStr);

        self::assertEquals($str, decodePath($newStr));
    }

    public function testEncodePathSegment(): void
    {
        $str = '';
        for ($i = 0; $i < 128; ++$i) {
            $str .= chr($i);
        }

        $newStr = encodePathSegment($str);

        // Note: almost exactly the same as the last test, except for
        // the encoding of / (ascii code 2f)
        self::assertEquals(
            '%00%01%02%03%04%05%06%07%08%09%0a%0b%0c%0d%0e%0f'.
            '%10%11%12%13%14%15%16%17%18%19%1a%1b%1c%1d%1e%1f'.
            '%20%21%22%23%24%25%26%27()%2a%2b%2c-.%2f'.
            '0123456789:%3b%3c%3d%3e%3f'.
            '@ABCDEFGHIJKLMNO'.
            'PQRSTUVWXYZ%5b%5c%5d%5e_'.
            '%60abcdefghijklmno'.
            'pqrstuvwxyz%7b%7c%7d~%7f',
            $newStr);

        self::assertEquals($str, decodePathSegment($newStr));
    }

    public function testDecode(): void
    {
        $str = 'Hello%20Test+Test2.txt';
        $newStr = decodePath($str);
        self::assertEquals('Hello Test+Test2.txt', $newStr);
    }

    /**
     * @depends testDecode
     */
    public function testDecodeUmlaut(): void
    {
        $str = 'Hello%C3%BC.txt';
        $newStr = decodePath($str);
        self::assertEquals("Hello\xC3\xBC.txt", $newStr);
    }

    /**
     * @depends testDecode
     */
    public function testDecodeSlavicWords(): void
    {
        $words = [
            'Ostroměr',
            'Šventaragis',
            'Świętopełk',
            'Dušan',
            'Živko',
        ];
        foreach ($words as $word) {
            $str = rawurlencode($word);
            $newStr = decodePath($str);
            self::assertEquals($word, $newStr);
        }
    }

    /**
     * @depends testDecodeUmlaut
     */
    public function testDecodeUmlautLatin1(): void
    {
        $str = 'Hello%FC.txt';
        $newStr = decodePath($str);
        self::assertEquals("Hello\xC3\xBC.txt", $newStr);
    }

    /**
     * This testcase was sent by a bug reporter.
     *
     * @depends testDecode
     */
    public function testDecodeAccentsWindows7(): void
    {
        $str = '/webdav/%C3%A0fo%C3%B3';
        $newStr = decodePath($str);
        self::assertEquals(strtolower($str), encodePath($newStr));
    }
}
