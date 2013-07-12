<?php

namespace Sabre\HTTP;

class MessageDecoratorTest extends \PHPUnit_Framework_TestCase {

    protected $inner;
    protected $outer;

    function setUp() {

        $this->inner = new Request();
        $this->outer = new RequestDecorator($this->inner);

    }

    function testBody() {

        $this->outer->setBody('foo');
        $this->assertEquals('foo', stream_get_contents($this->inner->getBody()));
        $this->assertEquals('foo', stream_get_contents($this->outer->getBody()));

    }

    function testHeaders() {

        $this->outer->setHeaders([
            'a' => 'b',
            ]);

        $this->assertEquals(['a' => 'b'], $this->inner->getHeaders());
        $this->assertEquals(['a' => 'b'], $this->outer->getHeaders());

    }

    function testHeader() {

        $this->outer->setHeader('a', 'c');

        $this->assertEquals('c', $this->inner->getHeader('A'));
        $this->assertEquals('c', $this->outer->getHeader('A'));

        $this->outer->removeHeader('a');

        $this->assertNull($this->inner->getHeader('A'));
        $this->assertNull($this->outer->getHeader('A'));
    }

    function testHttpVersion() {

        $this->outer->setHttpVersion('1.0');

        $this->assertEquals('1.0', $this->inner->getHttpVersion());
        $this->assertEquals('1.0', $this->outer->getHttpVersion());

    }

}
