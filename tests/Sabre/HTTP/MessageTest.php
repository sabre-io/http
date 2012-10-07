<?php

namespace Sabre\HTTP;

class MessageTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $message = new MessageMock();

    }

    function testBody() {

        $body = 'foo';
        $h = fopen('php://memory', 'r+');
        fwrite($h, $body);
        rewind($h);

        $message = new MessageMock();
        $message->setBody($body);

        $this->assertEquals($body, $message->getBody());

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


}

class MessageMock extends Message { }
