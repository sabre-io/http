<?php

namespace Sabre\HTTP;

class MessageTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $message = new MessageMock();
        $this->assertInstanceOf('Sabre\HTTP\Message', $message);

    }

    function testStreamBody() {

        $body = 'foo';
        $h = fopen('php://memory', 'r+');
        fwrite($h, $body);
        rewind($h);

        $message = new MessageMock();
        $message->setBody($h);

        $this->assertEquals($body, $message->getBody(Message::BODY_STRING));
        rewind($h);
        $this->assertEquals($body, stream_get_contents($message->getBody()));
        rewind($h);
        $this->assertEquals($body, stream_get_contents($message->getBody(Message::BODY_RAW)));

    }

    function testStringBody() {

        $body = 'foo';

        $message = new MessageMock();
        $message->setBody($body);

        $this->assertEquals($body, $message->getBody(Message::BODY_STRING));
        $this->assertEquals($body, stream_get_contents($message->getBody()));
        $this->assertEquals($body, $message->getBody(Message::BODY_RAW));

    }


    function testGetEmptyBody() {

        $message = new MessageMock();
        $body = $message->getBody();

        $this->assertEquals('', stream_get_contents($body));

    }

    function testHeaders() {

        $message = new MessageMock();
        $message->setHeader('X-Foo', 'bar');

        // Testing caselessness
        $this->assertEquals('bar', $message->getHeader('X-Foo'));
        $this->assertEquals('bar', $message->getHeader('x-fOO'));

        $this->assertTrue(
            $message->removeHeader('X-FOO')
        );
        $this->assertNull($message->getHeader('X-Foo'));
        $this->assertFalse(
            $message->removeHeader('X-FOO')
        );

    }

    function testSendBody() {

        $message = new MessageMock();

        // String
        $message->setBody('foo');

        // Stream
        $h = fopen('php://memory','r+');
        fwrite($h,'bar');
        rewind($h);
        $message->setBody($h);

        $body = $message->getBody();
        rewind($body);

        $this->assertEquals('bar', stream_get_contents($body));

    }

    function testSetHeaders() {

        $message = new MessageMock();
        $message->setHeaders([
            'a' => 'b',
        ]);
        $message->setHeaders([
            'c' => 'd',
            ]);
        $this->assertNull($message->getHeader('a'));

    }

    function testAddHeaders() {

        $message = new MessageMock();
        $message->addHeaders([
            'a' => 'b',
        ]);
        $message->addHeaders([
            'c' => 'd',
        ]);
        $this->assertEquals('b', $message->getHeader('a'));

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testGetBodyInvalid() {

        $message = new MessageMock();
        $message->getBody(-1);

    }

}

class MessageMock extends Message { }
