<?php

namespace Sabre\HTTP;

class ResponseTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $response = new Response(200, ['Content-Type' => 'text/xml']);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('OK', $response->getStatusText());

    }


    /**
     * @expectedException InvalidArgumentException
     */
    function testInvalidStatus() {

        $response = new Response(1000);

    }

}
