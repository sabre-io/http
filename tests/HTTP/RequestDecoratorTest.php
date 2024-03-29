<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class RequestDecoratorTest extends \PHPUnit\Framework\TestCase
{
    protected Request $inner;
    protected RequestDecorator $outer;

    public function setUp(): void
    {
        $this->inner = new Request('GET', '/');
        $this->outer = new RequestDecorator($this->inner);
    }

    public function testMethod(): void
    {
        $this->outer->setMethod('FOO');
        self::assertEquals('FOO', $this->inner->getMethod());
        self::assertEquals('FOO', $this->outer->getMethod());
    }

    public function testUrl(): void
    {
        $this->outer->setUrl('/foo');
        self::assertEquals('/foo', $this->inner->getUrl());
        self::assertEquals('/foo', $this->outer->getUrl());
    }

    public function testAbsoluteUrl(): void
    {
        $this->outer->setAbsoluteUrl('http://example.org/foo');
        self::assertEquals('http://example.org/foo', $this->inner->getAbsoluteUrl());
        self::assertEquals('http://example.org/foo', $this->outer->getAbsoluteUrl());
    }

    public function testBaseUrl(): void
    {
        $this->outer->setBaseUrl('/foo');
        self::assertEquals('/foo', $this->inner->getBaseUrl());
        self::assertEquals('/foo', $this->outer->getBaseUrl());
    }

    public function testPath(): void
    {
        $this->outer->setBaseUrl('/foo');
        $this->outer->setUrl('/foo/bar');
        self::assertEquals('bar', $this->inner->getPath());
        self::assertEquals('bar', $this->outer->getPath());
    }

    public function testQueryParams(): void
    {
        $this->outer->setUrl('/foo?a=b&c=d&e');
        $expected = [
            'a' => 'b',
            'c' => 'd',
            'e' => null,
        ];

        self::assertEquals($expected, $this->inner->getQueryParameters());
        self::assertEquals($expected, $this->outer->getQueryParameters());
    }

    public function testPostData(): void
    {
        $postData = [
            'a' => 'b',
            'c' => 'd',
            'e' => null,
        ];

        $this->outer->setPostData($postData);
        self::assertEquals($postData, $this->inner->getPostData());
        self::assertEquals($postData, $this->outer->getPostData());
    }

    public function testServerData(): void
    {
        $serverData = [
            'HTTPS' => 'On',
        ];

        $this->outer->setRawServerData($serverData);
        self::assertEquals('On', $this->inner->getRawServerValue('HTTPS'));
        self::assertEquals('On', $this->outer->getRawServerValue('HTTPS'));

        self::assertNull($this->inner->getRawServerValue('FOO'));
        self::assertNull($this->outer->getRawServerValue('FOO'));
    }

    public function testToString(): void
    {
        $this->inner->setMethod('POST');
        $this->inner->setUrl('/foo/bar/');
        $this->inner->setBody('foo');
        $this->inner->setHeader('foo', 'bar');

        self::assertEquals((string) $this->inner, (string) $this->outer);
    }
}
