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

        $r = Sapi::createFromServerArray($s);

        $this->assertEquals('http://sabredav.org/foo', $r->getAbsoluteUrl());

        $s = [
            'HTTP_HOST'   => 'sabredav.org',
            'REQUEST_URI' => '/foo',
            'HTTPS'       => 'on',
        ];

        $r = Sapi::createFromServerArray($s);

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

    function testGetPath() {

        $request = new Request();
        $request->setBaseUrl('/foo');
        $request->setUrl('/foo/bar/');

        $this->assertEquals('bar', $request->getPath());

    }

    function testGetPathStrippedQuery() {

        $request = new Request();
        $request->setBaseUrl('/foo');
        $request->setUrl('/foo/bar/?a=b');

        $this->assertEquals('bar', $request->getPath());

    }

    function testGetPathMissingSlash() {

        $request = new Request();
        $request->setBaseUrl('/foo/');
        $request->setUrl('/foo');

        $this->assertEquals('', $request->getPath());

    }

    /**
     * @expectedException \LogicException
     */
    function testGetPathOutsideBaseUrl() {

        $request = new Request();
        $request->setBaseUrl('/foo/');
        $request->setUrl('/bar/');

        $request->getPath();

    }
}
