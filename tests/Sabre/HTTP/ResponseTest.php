<?php

namespace Sabre\HTTP;

class ResponseTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $response = new Response(200, ['Content-Type' => 'text/xml']);
        $this->assertEquals('200 OK', $response->getStatus());

    }

    /**
     * @runInSeparateProcess
     *
     * Unfortunately we have no way of testing if the HTTP response code got
     * changed.
     */
    function testSend() {

        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('XDebug needs to be installed for this test to run');
        }

        $response = new Response(204, ['Content-Type', 'text/xml']);
        $response->setBody('foo');

        ob_start();

        $response->send();
        $headers = xdebug_get_headers();

        $result = ob_get_clean();
        header_remove();

        $this->assertEquals(
            [
                "0: Content-Type",
                "1: text/xml",
            ],
            $headers
        );

        $this->assertEquals('foo', $result);

    }

}
