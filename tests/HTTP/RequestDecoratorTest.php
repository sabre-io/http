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
        $this->assertEquals('FOO', $this->inner->getMethod());
        $this->assertEquals('FOO', $this->outer->getMethod());
    }

    public function testUrl(): void
    {
        $this->outer->setUrl('/foo');
        $this->assertEquals('/foo', $this->inner->getUrl());
        $this->assertEquals('/foo', $this->outer->getUrl());
    }

    public function testAbsoluteUrl(): void
    {
        $this->outer->setAbsoluteUrl('http://example.org/foo');
        $this->assertEquals('http://example.org/foo', $this->inner->getAbsoluteUrl());
        $this->assertEquals('http://example.org/foo', $this->outer->getAbsoluteUrl());
    }

    public function testBaseUrl(): void
    {
        $this->outer->setBaseUrl('/foo');
        $this->assertEquals('/foo', $this->inner->getBaseUrl());
        $this->assertEquals('/foo', $this->outer->getBaseUrl());
    }

    public function testPath(): void
    {
        $this->outer->setBaseUrl('/foo');
        $this->outer->setUrl('/foo/bar');
        $this->assertEquals('bar', $this->inner->getPath());
        $this->assertEquals('bar', $this->outer->getPath());
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
        $this->assertEquals('On', $this->inner->getRawServerValue('HTTPS'));
        $this->assertEquals('On', $this->outer->getRawServerValue('HTTPS'));

        $this->assertNull($this->inner->getRawServerValue('FOO'));
        $this->assertNull($this->outer->getRawServerValue('FOO'));
    }

    public function testToString(): void
    {
        $this->inner->setMethod('POST');
        $this->inner->setUrl('/foo/bar/');
        $this->inner->setBody('foo');
        $this->inner->setHeader('foo', 'bar');

        $this->assertEquals((string) $this->inner, (string) $this->outer);
    }
}
