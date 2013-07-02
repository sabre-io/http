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
            'SERVER_PROTOCOL' => 'HTTP/1.0',
        ));

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals(array(
            'User-Agent' => 'Evert',
            'Content-Type' => 'text/xml',
            'Content-Length' => '400',
        ), $request->getHeaders());

        $this->assertEquals('1.0', $request->getHttpVersion());

        $this->assertEquals('400', $request->getRawServerValue('CONTENT_LENGTH'));
        $this->assertNull($request->getRawServerValue('FOO'));

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

    function testGetQueryParameters() {

        $request = new Request('GET', '/foo?a=b&c&d=e');
        $this->assertEquals([
            'a' => 'b',
            'c' => null,
            'd' => 'e',
        ], $request->getQueryParameters());

    }

    function testGetQueryParametersNoData() {

        $request = new Request('GET', '/foo');
        $this->assertEquals([], $request->getQueryParameters());

    }

    /**
     * @backupGlobals
     */
    function testCreateFromPHPRequest() {

        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $request = Request::createFromPHPRequest();
        $this->assertEquals('PUT', $request->getMethod());

    }

    function testGetAbsoluteUrl() {

        $s = [
            'HTTP_HOST' => 'sabredav.org',
            'REQUEST_URI' => '/foo'
        ];

        $r = Request::createFromServerArray($s);

        $this->assertEquals('http://sabredav.org/foo', $r->getAbsoluteUrl());

        $s = [
            'HTTP_HOST'   => 'sabredav.org',
            'REQUEST_URI' => '/foo',
            'HTTPS'       => 'on',
        ];

        $r = Request::createFromServerArray($s);

        $this->assertEquals('https://sabredav.org/foo', $r->getAbsoluteUrl());

    }

    function testGetPostData() {

        $post = [
            'bla' => 'foo',
        ];
        $r = new Request();
        $r->setPostData($post);
        $this->assertEquals($post, $r->getPostData());

    }
}
