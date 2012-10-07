<?php

namespace Sabre\HTTP;

class ResponseTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $response = new Response(200, array('Content-Type' => 'text/xml'));
        $this->assertEquals('200 OK', $response->getStatus());

    }

}
