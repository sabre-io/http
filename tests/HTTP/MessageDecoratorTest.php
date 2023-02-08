<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class MessageDecoratorTest extends \PHPUnit\Framework\TestCase
{
    protected Request $inner;
    protected RequestDecorator $outer;

    public function setUp(): void
    {
        $this->inner = new Request('GET', '/');
        $this->outer = new RequestDecorator($this->inner);
    }

    public function testBody(): void
    {
        $this->outer->setBody('foo');
        self::assertEquals('foo', stream_get_contents($this->inner->getBodyAsStream()));
        self::assertEquals('foo', stream_get_contents($this->outer->getBodyAsStream()));
        self::assertEquals('foo', $this->inner->getBodyAsString());
        self::assertEquals('foo', $this->outer->getBodyAsString());
        self::assertEquals('foo', $this->inner->getBody());
        self::assertEquals('foo', $this->outer->getBody());
    }

    public function testHeaders(): void
    {
        $this->outer->setHeaders([
            'a' => 'b',
        ]);

        self::assertEquals(['a' => ['b']], $this->inner->getHeaders());
        self::assertEquals(['a' => ['b']], $this->outer->getHeaders());

        $this->outer->setHeaders([
            'c' => 'd',
        ]);

        self::assertEquals(['a' => ['b'], 'c' => ['d']], $this->inner->getHeaders());
        self::assertEquals(['a' => ['b'], 'c' => ['d']], $this->outer->getHeaders());

        $this->outer->addHeaders([
            'e' => 'f',
        ]);

        self::assertEquals(['a' => ['b'], 'c' => ['d'], 'e' => ['f']], $this->inner->getHeaders());
        self::assertEquals(['a' => ['b'], 'c' => ['d'], 'e' => ['f']], $this->outer->getHeaders());
    }

    public function testHeader(): void
    {
        self::assertFalse($this->outer->hasHeader('a'));
        self::assertFalse($this->inner->hasHeader('a'));
        $this->outer->setHeader('a', 'c');
        self::assertTrue($this->outer->hasHeader('a'));
        self::assertTrue($this->inner->hasHeader('a'));

        self::assertEquals('c', $this->inner->getHeader('A'));
        self::assertEquals('c', $this->outer->getHeader('A'));

        $this->outer->addHeader('A', 'd');

        self::assertEquals(
            ['c', 'd'],
            $this->inner->getHeaderAsArray('A')
        );
        self::assertEquals(
            ['c', 'd'],
            $this->outer->getHeaderAsArray('A')
        );

        $success = $this->outer->removeHeader('a');

        self::assertTrue($success);
        self::assertNull($this->inner->getHeader('A'));
        self::assertNull($this->outer->getHeader('A'));

        self::assertFalse($this->outer->removeHeader('i-dont-exist'));
    }

    public function testHttpVersion(): void
    {
        $this->outer->setHttpVersion('1.0');

        self::assertEquals('1.0', $this->inner->getHttpVersion());
        self::assertEquals('1.0', $this->outer->getHttpVersion());
    }
}
