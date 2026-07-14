<?php

declare(strict_types=1);

namespace Sabre\HTTP;

use PHPUnit\Framework\Attributes\DataProvider;

final class NegotiateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array<mixed, mixed> $available
     */
    #[DataProvider('negotiateData')]
    public function testNegotiate(?string $acceptHeader, array $available, ?string $expected): void
    {
        $this->assertEquals($expected, negotiateContentType($acceptHeader, $available));
    }

    /**
     * @return \Iterator<int, array<int, mixed>>
     */
    public static function negotiateData(): \Iterator
    {
        yield [ // simple
            'application/xml',
            ['application/xml'],
            'application/xml',
        ];
        yield [ // no header
            null,
            ['application/xml'],
            'application/xml',
        ];
        yield [ // 2 options
            'application/json',
            ['application/xml', 'application/json'],
            'application/json',
        ];
        yield [ // 2 choices
            'application/json, application/xml',
            ['application/xml'],
            'application/xml',
        ];
        yield [ // quality
            'application/xml;q=0.2, application/json',
            ['application/xml', 'application/json'],
            'application/json',
        ];
        yield [ // wildcard
            'image/jpeg, image/png, */*',
            ['application/xml', 'application/json'],
            'application/xml',
        ];
        yield [ // wildcard + quality
            'image/jpeg, image/png; q=0.5, */*',
            ['application/xml', 'application/json', 'image/png'],
            'application/xml',
        ];
        yield [ // no match
            'image/jpeg',
            ['application/xml'],
            null,
        ];
        yield [ // This is used in sabre/dav
            'text/vcard; version=4.0',
            [
                // Most often used mime-type. Version 3
                'text/x-vcard',
                // The correct standard mime-type. Defaults to version 3 as
                // well.
                'text/vcard',
                // vCard 4
                'text/vcard; version=4.0',
                // vCard 3
                'text/vcard; version=3.0',
                // jCard
                'application/vcard+json',
            ],
            'text/vcard; version=4.0',
        ];
        yield [ // rfc7231 example 1
            'audio/*; q=0.2, audio/basic',
            [
                'audio/pcm',
                'audio/basic',
            ],
            'audio/basic',
        ];
        yield [ // Lower quality after
            'audio/pcm; q=0.2, audio/basic; q=0.1',
            [
                'audio/pcm',
                'audio/basic',
            ],
            'audio/pcm',
        ];
        yield [ // Random parameter, should be ignored
            'audio/pcm; hello; q=0.2, audio/basic; q=0.1',
            [
                'audio/pcm',
                'audio/basic',
            ],
            'audio/pcm',
        ];
        yield [ // No whitespace after type, should pick the one that is the most specific.
            'text/vcard;version=3.0, text/vcard',
            [
                'text/vcard',
                'text/vcard; version=3.0',
            ],
            'text/vcard; version=3.0',
        ];
        yield [ // Same as last one, but order is different
            'text/vcard, text/vcard;version=3.0',
            [
                'text/vcard; version=3.0',
                'text/vcard',
            ],
            'text/vcard; version=3.0',
        ];
        yield [ // Charset should be ignored here.
            'text/vcard; charset=utf-8; version=3.0, text/vcard',
            [
                'text/vcard',
                'text/vcard; version=3.0',
            ],
            'text/vcard; version=3.0',
        ];
        yield [ // Undefined offset issue.
            'text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2',
            ['application/xml', 'application/json', 'image/png'],
            'application/xml',
        ];
    }
}
