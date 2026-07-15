<?php

declare(strict_types=1);

namespace Sabre\HTTP;

final class RequestDecoratorTest extends \PHPUnit\Framework\TestCase
{
    private Request $inner;

    private RequestDecorator $outer;

    protected function setUp(): void
    {
        $this->inner = new Request('GET', '/');
        $this->outer = new RequestDecorator($this->inner);
    }

    public function testMethod(): void
    {
        $this->outer->setMethod('FOO');
        $this->assertSame('FOO', $this->inner->getMethod());
        $this->assertSame('FOO', $this->outer->getMethod());
    }

    public function testUrl(): void
    {
        $this->outer->setUrl('/foo');
        $this->assertSame('/foo', $this->inner->getUrl());
        $this->assertSame('/foo', $this->outer->getUrl());
    }

    public function testAbsoluteUrl(): void
    {
        $this->outer->setAbsoluteUrl('http://example.org/foo');
        $this->assertSame('http://example.org/foo', $this->inner->getAbsoluteUrl());
        $this->assertSame('http://example.org/foo', $this->outer->getAbsoluteUrl());
    }

    public function testBaseUrl(): void
    {
        $this->outer->setBaseUrl('/foo');
        $this->assertSame('/foo', $this->inner->getBaseUrl());
        $this->assertSame('/foo', $this->outer->getBaseUrl());
    }

    public function testPath(): void
    {
        $this->outer->setBaseUrl('/foo');
        $this->outer->setUrl('/foo/bar');
        $this->assertSame('bar', $this->inner->getPath());
        $this->assertSame('bar', $this->outer->getPath());
    }

    public function testQueryParams(): void
    {
        $this->outer->setUrl('/foo?a=b&c=d&e');
        $expected = [
            'a' => 'b',
            'c' => 'd',
            'e' => null,
        ];

        $this->assertEquals($expected, $this->inner->getQueryParameters());
        $this->assertEquals($expected, $this->outer->getQueryParameters());
    }

    public function testPostData(): void
    {
        $postData = [
            'a' => 'b',
            'c' => 'd',
            'e' => null,
        ];

        $this->outer->setPostData($postData);
        $this->assertEquals($postData, $this->inner->getPostData());
        $this->assertEquals($postData, $this->outer->getPostData());
    }

    public function testServerData(): void
    {
        $serverData = [
            'HTTPS' => 'On',
        ];

        $this->outer->setRawServerData($serverData);
        $this->assertSame('On', $this->inner->getRawServerValue('HTTPS'));
        $this->assertSame('On', $this->outer->getRawServerValue('HTTPS'));

        $this->assertNull($this->inner->getRawServerValue('FOO'));
        $this->assertNull($this->outer->getRawServerValue('FOO'));
    }

    public function testToString(): void
    {
        $this->inner->setMethod('POST');
        $this->inner->setUrl('/foo/bar/');
        $this->inner->setBody('foo');
        $this->inner->setHeader('foo', 'bar');

        $this->assertSame((string) $this->inner, (string) $this->outer);
    }
}
