<?php

namespace Sabre\HTTP;

class RequestTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $request = new Request('GET', '/foo', array(
            'User-Agent' => 'Evert',
        ));
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals(array(
            'User-Agent' => 'Evert',
        ), $request->getHeaders());

    }

    function testConstructFromServerArray() {

        $request = Request::createFromServerArray(array(
            'REQUEST_URI'     => '/foo',
            'REQUEST_METHOD'  => 'GET',
            'HTTP_USER_AGENT' => 'Evert',
            'CONTENT_TYPE'    => 'text/xml',
            'CONTENT_LENGTH'  => '400', 
        ));

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals(array(
            'User-Agent' => 'Evert',
            'Content-Type' => 'text/xml',
            'Content-Length' => '400',
        ), $request->getHeaders());

    }

    function testConstructPHPAuth() {

        $request = Request::createFromServerArray(array(
            'REQUEST_URI'     => '/foo',
            'REQUEST_METHOD'  => 'GET',
            'PHP_AUTH_USER'   => 'user',
            'PHP_AUTH_PW'     => 'pass',
        ));

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals(array(
            'Authorization' => 'Basic ' . base64_encode('user:pass'),
        ), $request->getHeaders());

    }

    function testConstructPHPAuthDigest() {

        $request = Request::createFromServerArray(array(
            'REQUEST_URI'     => '/foo',
            'REQUEST_METHOD'  => 'GET',
            'PHP_AUTH_DIGEST' => 'blabla',
        ));

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals(array(
            'Authorization' => 'Digest blabla',
        ), $request->getHeaders());

    }

    function testConstructRedirectAuth() {

        $request = Request::createFromServerArray(array(
            'REQUEST_URI'                 => '/foo',
            'REQUEST_METHOD'              => 'GET',
            'REDIRECT_HTTP_AUTHORIZATION' => 'Basic bla',
        ));

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals(array(
            'Authorization' => 'Basic bla',
        ), $request->getHeaders());

    }
}
