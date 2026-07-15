<?php

declare(strict_types=1);

namespace Sabre\HTTP;

final class ResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/xml']);
        $this->assertSame(200, $response->getStatus());
        $this->assertSame('OK', $response->getStatusText());
    }

    public function testSetStatus(): void
    {
        $response = new Response();
        $response->setStatus("402 Where's my money?");
        $this->assertSame(402, $response->getStatus());
        $this->assertSame("Where's my money?", $response->getStatusText());
    }

    public function testSetStatusWithoutText(): void
    {
        $response = new Response();
        $response->setStatus('402');
        $this->assertSame(402, $response->getStatus());
        $this->assertSame('Payment Required', $response->getStatusText());
    }

    public function testInvalidStatus(): void
    {
        $this->expectException('InvalidArgumentException');
        new Response(1000);
    }

    public function testToString(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/xml']);
        $response->setBody('foo');

        $expected = "HTTP/1.1 200 OK\r\n"
                  ."Content-Type: text/xml\r\n"
                  ."\r\n"
                  .'foo';
        $this->assertSame($expected, (string) $response);
    }
}
