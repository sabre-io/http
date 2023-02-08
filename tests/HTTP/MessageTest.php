<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class MessageTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct(): void
    {
        $message = new MessageMock();
        self::assertInstanceOf('Sabre\HTTP\Message', $message);
    }

    public function testStreamBody(): void
    {
        $body = 'foo';
        $h = fopen('php://memory', 'r+');
        fwrite($h, $body);
        rewind($h);

        $message = new MessageMock();
        $message->setBody($h);

        self::assertEquals($body, $message->getBodyAsString());
        rewind($h);
        self::assertEquals($body, stream_get_contents($message->getBodyAsStream()));
        rewind($h);
        self::assertEquals($body, stream_get_contents($message->getBody()));
    }

    public function testStringBody(): void
    {
        $body = 'foo';

        $message = new MessageMock();
        $message->setBody($body);

        self::assertEquals($body, $message->getBodyAsString());
        self::assertEquals($body, stream_get_contents($message->getBodyAsStream()));
        self::assertEquals($body, $message->getBody());
    }

    public function testCallbackBodyAsString(): void
    {
        $body = $this->createCallback('foo');

        $message = new MessageMock();
        $message->setBody($body);

        $string = $message->getBodyAsString();

        self::assertSame('foo', $string);
    }

    public function testCallbackBodyAsStream(): void
    {
        $body = $this->createCallback('foo');

        $message = new MessageMock();
        $message->setBody($body);

        $stream = $message->getBodyAsStream();

        self::assertSame('foo', stream_get_contents($stream));
    }

    public function testGetBodyWhenCallback(): void
    {
        $callback = $this->createCallback('foo');

        $message = new MessageMock();
        $message->setBody($callback);

        self::assertSame($callback, $message->getBody());
    }

    /**
     * It's possible that streams contains more data than the Content-Length.
     *
     * The request object should make sure to never emit more than
     * Content-Length, if Content-Length is set.
     *
     * This is in particular useful when responding to range requests with
     * streams that represent files on the filesystem, as it's possible to just
     * seek the stream to a certain point, set the content-length and let the
     * request object do the rest.
     */
    public function testLongStreamToStringBody(): void
    {
        $body = fopen('php://memory', 'r+');
        fwrite($body, 'abcdefg');
        fseek($body, 2);

        $message = new MessageMock();
        $message->setBody($body);
        $message->setHeader('Content-Length', '4');

        self::assertEquals(
            'cdef',
            $message->getBodyAsString()
        );
    }

    /**
     * Some clients include a content-length header, but the header is empty.
     * This is definitely broken behavior, but we should support it.
     */
    public function testEmptyContentLengthHeader(): void
    {
        $body = fopen('php://memory', 'r+');
        fwrite($body, 'abcdefg');
        fseek($body, 2);

        $message = new MessageMock();
        $message->setBody($body);
        $message->setHeader('Content-Length', '');

        self::assertEquals(
            'cdefg',
            $message->getBodyAsString()
        );
    }

    public function testGetEmptyBodyStream(): void
    {
        $message = new MessageMock();
        $body = $message->getBodyAsStream();

        self::assertEquals('', stream_get_contents($body));
    }

    public function testGetEmptyBodyString(): void
    {
        $message = new MessageMock();
        $body = $message->getBodyAsString();

        self::assertEquals('', $body);
    }

    public function testHeaders(): void
    {
        $message = new MessageMock();
        $message->setHeader('X-Foo', 'bar');

        // Testing caselessness
        self::assertEquals('bar', $message->getHeader('X-Foo'));
        self::assertEquals('bar', $message->getHeader('x-fOO'));

        self::assertTrue(
            $message->removeHeader('X-FOO')
        );
        self::assertNull($message->getHeader('X-Foo'));
        self::assertFalse(
            $message->removeHeader('X-FOO')
        );
    }

    public function testSetHeaders(): void
    {
        $message = new MessageMock();

        $headers = [
            'X-Foo' => ['1'],
            'X-Bar' => ['2'],
        ];

        $message->setHeaders($headers);
        self::assertEquals($headers, $message->getHeaders());

        $message->setHeaders([
            'X-Foo' => ['3', '4'],
            'X-Bar' => '5',
        ]);

        $expected = [
            'X-Foo' => ['3', '4'],
            'X-Bar' => ['5'],
        ];

        self::assertEquals($expected, $message->getHeaders());
    }

    public function testAddHeaders(): void
    {
        $message = new MessageMock();

        $headers = [
            'X-Foo' => ['1'],
            'X-Bar' => ['2'],
        ];

        $message->addHeaders($headers);
        self::assertEquals($headers, $message->getHeaders());

        $message->addHeaders([
            'X-Foo' => ['3', '4'],
            'X-Bar' => '5',
        ]);

        $expected = [
            'X-Foo' => ['1', '3', '4'],
            'X-Bar' => ['2', '5'],
        ];

        self::assertEquals($expected, $message->getHeaders());
    }

    public function testSendBody(): void
    {
        $message = new MessageMock();

        // String
        $message->setBody('foo');

        // Stream
        $h = fopen('php://memory', 'r+');
        fwrite($h, 'bar');
        rewind($h);
        $message->setBody($h);

        $body = $message->getBody();
        rewind($body);

        self::assertEquals('bar', stream_get_contents($body));
    }

    public function testMultipleHeaders(): void
    {
        $message = new MessageMock();
        $message->setHeader('a', '1');
        $message->addHeader('A', '2');

        self::assertEquals(
            '1,2',
            $message->getHeader('A')
        );
        self::assertEquals(
            '1,2',
            $message->getHeader('a')
        );

        self::assertEquals(
            ['1', '2'],
            $message->getHeaderAsArray('a')
        );
        self::assertEquals(
            ['1', '2'],
            $message->getHeaderAsArray('A')
        );
        self::assertEquals(
            [],
            $message->getHeaderAsArray('B')
        );
    }

    public function testHasHeaders(): void
    {
        $message = new MessageMock();

        self::assertFalse($message->hasHeader('X-Foo'));
        $message->setHeader('X-Foo', 'Bar');
        self::assertTrue($message->hasHeader('X-Foo'));
    }

    /**
     * @param string $content
     *
     * @return \Closure Returns a callback printing $content to php://output stream
     */
    private function createCallback($content)
    {
        return function () use ($content) {
            echo $content;
        };
    }
}

class MessageMock extends Message
{
    public function __toString(): string
    {
        return 'mock text';
    }
}
