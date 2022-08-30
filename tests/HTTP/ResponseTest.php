<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class ResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/xml']);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('OK', $response->getStatusText());
    }

    public function testSetStatus(): void
    {
        $response = new Response();
        $response->setStatus('402 Where\'s my money?');
        $this->assertEquals(402, $response->getStatus());
        $this->assertEquals('Where\'s my money?', $response->getStatusText());
    }

    public function testSetStatusWithoutText(): void
    {
        $response = new Response();
        $response->setStatus('402');
        $this->assertEquals(402, $response->getStatus());
        $this->assertEquals('Payment Required', $response->getStatusText());
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
        $this->assertEquals($expected, (string) $response);
    }
}
