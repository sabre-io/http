<?php

declare(strict_types=1);

namespace Sabre\HTTP;

final class MessageDecoratorTest extends \PHPUnit\Framework\TestCase
{
    private Request $inner;

    private RequestDecorator $outer;

    protected function setUp(): void
    {
        $this->inner = new Request('GET', '/');
        $this->outer = new RequestDecorator($this->inner);
    }

    public function testBody(): void
    {
        $this->outer->setBody('foo');
        $this->assertEquals('foo', stream_get_contents($this->inner->getBodyAsStream()));
        $this->assertEquals('foo', stream_get_contents($this->outer->getBodyAsStream()));
        $this->assertSame('foo', $this->inner->getBodyAsString());
        $this->assertSame('foo', $this->outer->getBodyAsString());
        $this->assertEquals('foo', $this->inner->getBody());
        $this->assertEquals('foo', $this->outer->getBody());
    }

    public function testHeaders(): void
    {
        $this->outer->setHeaders([
            'a' => 'b',
        ]);

        $this->assertSame(['a' => ['b']], $this->inner->getHeaders());
        $this->assertSame(['a' => ['b']], $this->outer->getHeaders());

        $this->outer->setHeaders([
            'c' => 'd',
        ]);

        $this->assertSame(['a' => ['b'], 'c' => ['d']], $this->inner->getHeaders());
        $this->assertSame(['a' => ['b'], 'c' => ['d']], $this->outer->getHeaders());

        $this->outer->addHeaders([
            'e' => 'f',
        ]);

        $this->assertSame(['a' => ['b'], 'c' => ['d'], 'e' => ['f']], $this->inner->getHeaders());
        $this->assertSame(['a' => ['b'], 'c' => ['d'], 'e' => ['f']], $this->outer->getHeaders());
    }

    public function testHeader(): void
    {
        $this->assertFalse($this->outer->hasHeader('a'));
        $this->assertFalse($this->inner->hasHeader('a'));
        $this->outer->setHeader('a', 'c');
        $this->assertTrue($this->outer->hasHeader('a'));
        $this->assertTrue($this->inner->hasHeader('a'));

        $this->assertSame('c', $this->inner->getHeader('A'));
        $this->assertSame('c', $this->outer->getHeader('A'));

        $this->outer->addHeader('A', 'd');

        $this->assertSame(['c', 'd'], $this->inner->getHeaderAsArray('A'));
        $this->assertSame(['c', 'd'], $this->outer->getHeaderAsArray('A'));

        $success = $this->outer->removeHeader('a');

        $this->assertTrue($success);
        $this->assertNull($this->inner->getHeader('A'));
        $this->assertNull($this->outer->getHeader('A'));

        $this->assertFalse($this->outer->removeHeader('i-dont-exist'));
    }

    public function testHttpVersion(): void
    {
        $this->outer->setHttpVersion('1.0');

        $this->assertEquals('1.0', $this->inner->getHttpVersion());
        $this->assertEquals('1.0', $this->outer->getHttpVersion());
    }
}
