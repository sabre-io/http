<?php

namespace Sabre\HTTP;

class ResponseDecoratorTest extends \PHPUnit_Framework_TestCase {

    protected $inner;
    protected $outer;

    function setUp() {

        $this->inner = new Response();
        $this->outer = new ResponseDecorator($this->inner);

    }

    /**
     * @runInSeparateProcess
     */
    function testSend() {

        $this->inner->setBody('foo');

        ob_start();
        $this->outer->send();

        $this->assertEquals('foo', ob_get_clean());

    }


    function testStatus() {

        $this->outer->setStatus(201);
        $this->assertEquals('201 Created', $this->inner->getStatus());
        $this->assertEquals('201 Created', $this->outer->getStatus());

    }

}
