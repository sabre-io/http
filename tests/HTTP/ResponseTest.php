<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class ResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/xml']);
        self::assertEquals(200, $response->getStatus());
        self::assertEquals('OK', $response->getStatusText());
    }

    public function testSetStatus(): void
    {
        $response = new Response();
        $response->setStatus('402 Where\'s my money?');
        self::assertEquals(402, $response->getStatus());
        self::assertEquals('Where\'s my money?', $response->getStatusText());
    }

    public function testSetStatusWithoutText(): void
    {
        $response = new Response();
        $response->setStatus('402');
        self::assertEquals(402, $response->getStatus());
        self::assertEquals('Payment Required', $response->getStatusText());
    }

    public function testInvalidStatus(): void
    {
        $this->expectException('InvalidArgumentException');
        $response = new Response(1000);
    }

    public function testToString(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/xml']);
        $response->setBody('foo');

        $expected = "HTTP/1.1 200 OK\r\n"
                  ."Content-Type: text/xml\r\n"
                  ."\r\n"
                  .'foo';
        self::assertEquals($expected, (string) $response);
    }
}
