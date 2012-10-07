<?php

namespace Sabre\HTTP;

class ClientTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $client = new ClientMock(array(
            'url' => 'http://example.org/root/',
        ));


    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testConstructNoUrl() {

        $client = new ClientMock(array());

    }

    function testRequest() {

        $client = new ClientMock(array(
            'url' => 'http://example.org/root/',
            'userName' => 'user',
            'password' => 'pass',
            'proxy'    => 'localhost:8888',
        ));

        $body = fopen('php://memory', 'r+');
        fwrite($body, 'hi!');
        rewind($body);

        $request = new Request('GET', 'boo', array(
            'User-Agent' => 'Evert',
        ), $body);

        $fakeResponse = array(
            "HTTP/1.1 200 OK\r\nHeader: value\r\n\r\nBody",
            array(
                'http_code' => 200,
                'header_size' => 34 
            ),
            0,
            "no error",
        );
        $client->response = $fakeResponse;

        $response = $client->request($request);

        $this->assertEquals('200 OK', $response->getStatus());
        $this->assertEquals('Body', stream_get_contents($response->getBody()));
        $this->assertEquals(array(
            'Header' => 'value',
        ), $response->getHeaders());

        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => 'hi!',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_URL => 'http://example.org/root/boo',
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'User-Agent: Evert',
            ),
            CURLOPT_PROXY => 'localhost:8888',
            CURLOPT_USERPWD => 'user:pass',
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC | CURLAUTH_DIGEST,
        ), $client->settings);



    }

    function testHEAD() {

        $client = new ClientMock(array(
            'url' => 'http://example.org/root/',
        ));

        $request = new Request('HEAD', '/foo', array(
            'User-Agent' => 'Evert',
        ));

        $fakeResponse = array(
            "HTTP/1.1 200 OK\r\nHeader: value\r\n\r\nBody",
            array(
                'http_code' => 200,
                'header_size' => 34 
            ),
            0,
            "no error",
        );
        $client->response = $fakeResponse;

        $response = $client->request($request);

        $this->assertEquals('200 OK', $response->getStatus());
        $this->assertEquals('Body', stream_get_contents($response->getBody()));
        $this->assertEquals(array(
            'Header' => 'value',
        ), $response->getHeaders());

        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => null,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_URL => 'http://example.org/foo',
            CURLOPT_NOBODY => true,
            CURLOPT_CUSTOMREQUEST => 'HEAD',
            CURLOPT_HTTPHEADER => array(
                'User-Agent: Evert',
            ),
        ), $client->settings);

    }

    /**
     * @expectedException Sabre\HTTP\ClientException
     */
    function testError() {

        $client = new ClientMock(array(
            'url' => 'http://example.org/root/',
        ));

        $request = new Request('HEAD', 'http://example.net/foo', array(
            'User-Agent' => 'Evert',
        ));

        $fakeResponse = array(
            "HTTP/1.1 200 OK\r\nHeader: value\r\n\r\nBody",
            array(
                'http_code' => 200,
                'header_size' => 34 
            ),
            2,
            "an error!",
        );
        $client->response = $fakeResponse;

        $response = $client->request($request);

    }
}

class ClientMock extends Client {

    public $settings;
    public $response; 

    /**
     * Wrapper for all curl functions.
     *
     * The only reason this was split out in a separate method, is so it
     * becomes easier to unittest.
     *
     * @param string $url
     * @param array $settings
     * @return array
     */
    protected function curlRequest($settings) {

        $this->settings = $settings;
        return $this->response;

    }

}
