<?php

declare(strict_types=1);

namespace Sabre\HTTP;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

final class SapiTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructFromServerArray(): void
    {
        $request = Sapi::createFromServerArray([
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'HTTP_USER_AGENT' => 'Evert',
            'CONTENT_TYPE' => 'text/xml',
            'CONTENT_LENGTH' => '400',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
        ]);

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/foo', $request->getUrl());
        $this->assertSame([
            'User-Agent' => ['Evert'],
            'Content-Type' => ['text/xml'],
            'Content-Length' => ['400'],
        ], $request->getHeaders());

        $this->assertEquals('1.0', $request->getHttpVersion());

        $this->assertEquals('400', $request->getRawServerValue('CONTENT_LENGTH'));
        $this->assertNull($request->getRawServerValue('FOO'));
    }

    public function testConstructFromServerArrayOnNullUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The _SERVER array must have a REQUEST_URI key');

        Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'HTTP_USER_AGENT' => 'Evert',
            'CONTENT_TYPE' => 'text/xml',
            'CONTENT_LENGTH' => '400',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
        ]);
    }

    public function testConstructFromServerArrayOnNullMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The _SERVER array must have a REQUEST_METHOD key');

        Sapi::createFromServerArray([
            'REQUEST_URI' => '/foo',
            'HTTP_USER_AGENT' => 'Evert',
            'CONTENT_TYPE' => 'text/xml',
            'CONTENT_LENGTH' => '400',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
        ]);
    }

    public function testConstructPHPAuth(): void
    {
        $request = Sapi::createFromServerArray([
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW' => 'pass',
        ]);

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/foo', $request->getUrl());
        $this->assertEquals([
            'Authorization' => ['Basic '.base64_encode('user:pass')],
        ], $request->getHeaders());
    }

    public function testConstructPHPAuthDigest(): void
    {
        $request = Sapi::createFromServerArray([
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'PHP_AUTH_DIGEST' => 'blabla',
        ]);

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/foo', $request->getUrl());
        $this->assertSame([
            'Authorization' => ['Digest blabla'],
        ], $request->getHeaders());
    }

    public function testConstructRedirectAuth(): void
    {
        $request = Sapi::createFromServerArray([
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'REDIRECT_HTTP_AUTHORIZATION' => 'Basic bla',
        ]);

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/foo', $request->getUrl());
        $this->assertSame([
            'Authorization' => ['Basic bla'],
        ], $request->getHeaders());
    }

    /**
     * @runInSeparateProcess
     *
     * Unfortunately we have no way of testing if the HTTP response code got
     * changed.
     */
    public function testSend(): void
    {
        if (!function_exists('xdebug_get_headers')) {
            self::markTestSkipped('XDebug needs to be installed for this test to run');
        }

        $response = new Response(204, ['Content-Type' => 'text/xml;charset=UTF-8']);

        // Second Content-Type header. Normally this doesn't make sense.
        $response->addHeader('Content-Type', 'application/xml');
        $response->setBody('foo');

        ob_start();

        Sapi::sendResponse($response);
        $headers = xdebug_get_headers();

        $result = ob_get_clean();
        header_remove();

        $this->assertSame([
            'Content-Type: text/xml;charset=UTF-8',
            'Content-Type: application/xml',
        ], $headers);

        $this->assertEquals('foo', $result);
    }

    /**
     * @runInSeparateProcess
     */
    #[Depends('testSend')]
    public function testSendLimitedByContentLengthString(): void
    {
        $response = new Response(200);

        $response->addHeader('Content-Length', 19);
        $response->setBody('Send this sentence. Ignore this one.');

        ob_start();

        Sapi::sendResponse($response);

        $result = ob_get_clean();
        header_remove();

        $this->assertEquals('Send this sentence.', $result);
    }

    /**
     * Tests whether http2 is recognized.
     */
    public function testRecognizeHttp2(): void
    {
        $request = Sapi::createFromServerArray([
            'SERVER_PROTOCOL' => 'HTTP/2.0',
            'REQUEST_URI' => 'bla',
            'REQUEST_METHOD' => 'GET',
        ]);

        $this->assertEquals('2.0', $request->getHttpVersion());
    }

    /**
     * @runInSeparateProcess
     */
    #[Depends('testSend')]
    public function testSendLimitedByContentLengthStream(): void
    {
        $response = new Response(200, ['Content-Length' => 19]);

        $body = fopen('php://memory', 'w');
        fwrite($body, 'Ignore this. Send this sentence. Ignore this too.');
        rewind($body);
        fread($body, 13);
        $response->setBody($body);

        ob_start();

        Sapi::sendResponse($response);

        $result = ob_get_clean();
        header_remove();

        $this->assertEquals('Send this sentence.', $result);
    }

    /**
     * @runInSeparateProcess
     */
    #[Depends('testSend')]
    #[DataProvider('sendContentRangeStreamData')]
    public function testSendContentRangeStream(
        string $ignoreAtStart,
        string $sendText,
        int $multiplier,
        string $ignoreAtEnd,
        ?int $contentLength = null): void
    {
        $partial = str_repeat($sendText, $multiplier);
        $ignoreAtStartLength = strlen($ignoreAtStart);
        $ignoreAtEndLength = strlen($ignoreAtEnd);
        $body = fopen('php://memory', 'w');
        if (null === $contentLength) {
            $contentLength = strlen($partial);
        }

        fwrite($body, $ignoreAtStart);
        fwrite($body, $partial);
        if ($ignoreAtEndLength > 0) {
            fwrite($body, $ignoreAtEnd);
        }

        rewind($body);
        if ($ignoreAtStartLength > 0) {
            fread($body, $ignoreAtStartLength);
        }

        $response = new Response(200, [
            'Content-Length' => $contentLength,
            'Content-Range' => sprintf('bytes %d-%d/%d', $ignoreAtStartLength, $ignoreAtStartLength + strlen($partial) - 1, $ignoreAtStartLength + strlen($partial) + $ignoreAtEndLength),
        ]);
        $response->setBody($body);

        ob_start();

        Sapi::sendResponse($response);

        $result = ob_get_clean();
        header_remove();

        $this->assertEquals($partial, $result);
    }

    /**
     * @return \Iterator<int, array<int, mixed>>
     */
    public static function sendContentRangeStreamData(): \Iterator
    {
        yield ['Ignore this. ', 'Send this.', 10, ' Ignore this at end.'];
        yield ['Ignore this. ', 'Send this.', 1000, ' Ignore this at end.'];
        yield ['Ignore this. ', 'S', 4096, ' Ignore this at end.'];
        yield ['I', 'S', 4094, 'E'];
        yield ['', 'Send this.', 10, ' Ignore this at end.'];
        yield ['', 'Send this.', 1000, ' Ignore this at end.'];
        yield ['', 'S', 4096, ' Ignore this at end.'];
        yield ['', 'S', 4094, 'En'];
        yield ['Ignore this. ', 'Send this.', 10, ''];
        yield ['Ignore this. ', 'Send this.', 1000, ''];
        yield ['Ignore this. ', 'S', 4096, ''];
        yield ['Ig', 'S', 4094, ''];
        // Provide contentLength greater than the bytes remaining in the stream.
        yield ['Ignore this. ', 'Send this.', 10, '', 101];
        yield ['Ignore this. ', 'Send this.', 1000, '', 10001];
        yield ['Ignore this. ', 'S', 4096, '', 5000000];
        yield ['I', 'S', 4094, '', 8095];
        // Provide contentLength equal to the bytes remaining in the stream.
        yield ['', 'Send this.', 10, '', 100];
        yield ['Ignore this. ', 'Send this.', 1000, '', 10000];
    }

    /**
     * @runInSeparateProcess
     */
    #[Depends('testSend')]
    public function testSendWorksWithCallbackAsBody(): void
    {
        $response = new Response(200, [], function (): void {
            $fd = fopen('php://output', 'r+');
            fwrite($fd, 'foo');
            fclose($fd);
        });

        ob_start();

        Sapi::sendResponse($response);

        $result = ob_get_clean();

        $this->assertEquals('foo', $result);
    }

    public function testSendConnectionAborted(): void
    {
        $baseUrl = getenv('BASEURL');
        if (!$baseUrl) {
            self::markTestSkipped('Set an environment value BASEURL to continue');
        }

        $url = rtrim($baseUrl, '/').'/connection_aborted.php';
        $chunk_size = 4 * 1024 * 1024;
        $fetch_size = 6 * 1024 * 1024;

        $stream = fopen($url, 'r');
        $size = 0;

        while ($size <= $fetch_size) {
            $temp = fread($stream, 8192);
            if (false === $temp) {
                break;
            }

            $size += strlen($temp);
        }

        fclose($stream);

        sleep(5);

        $bytes_read = file_get_contents(sys_get_temp_dir().'/dummy_stream_read_counter');
        $this->assertEquals($chunk_size * 2, $bytes_read);
        $this->assertGreaterThanOrEqual($fetch_size, $bytes_read);
    }
}
