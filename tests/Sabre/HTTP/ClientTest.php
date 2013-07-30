<?php

namespace Sabre\HTTP;

class ClientTest extends \PHPUnit_Framework_TestCase {

    protected $client;

    function testSendGet() {

        $client = new ClientMock();

        $client->on('curl', function($settings, &$result) {

            $this->assertEquals([
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_POSTREDIR => 0,
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_POSTFIELDS => null,
                CURLOPT_PUT => false,
            ], $settings);

            $returnHeaders = [
                "HTTP/1.1 200 OK",
                "X-Zim: Gir",
            ];

            $returnHeaders = implode("\r\n", $returnHeaders) . "\r\n\r\n";

            $returnBody = "hi!";

            $result = [
                $returnHeaders . $returnBody,
                [
                    'header_size' => strlen($returnHeaders),
                    'http_code' => 200,
                ],
                0,
                '',
            ];


        });

        $client->addCurlSetting(CURLOPT_POSTREDIR, 0);

        $request = new Request('GET','http://example.org/', ['X-Foo' => 'bar']);
        $response = $client->send($request);

        $this->assertEquals(
            '200 OK',
            $response->getStatus()
        );

        $this->assertEquals(
            'Gir',
            $response->getHeader('X-Zim')
        );

        $this->assertEquals(
            'hi!',
            $response->getBody(Message::BODY_STRING)
        );

    }

    function testSendHead() {

        $client = new ClientMock();

        $client->on('curl', function($settings, &$result) {

            $this->assertEquals([
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_POSTREDIR => 3,
                CURLOPT_NOBODY => true,
                CURLOPT_CUSTOMREQUEST => 'HEAD',
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
                CURLOPT_POSTFIELDS => null,
                CURLOPT_PUT => false,
            ], $settings);

            $returnHeaders = [
                "HTTP/1.1 200 OK",
                "X-Zim: Gir",
            ];

            $returnHeaders = implode("\r\n", $returnHeaders) . "\r\n\r\n";

            $returnBody = "hi!";

            $result = [
                $returnHeaders . $returnBody,
                [
                    'header_size' => strlen($returnHeaders),
                    'http_code' => 200,
                ],
                0,
                '',
            ];


        });
        $request = new Request('HEAD','http://example.org/', ['X-Foo' => 'bar']);
        $response = $client->send($request);

        $this->assertEquals(
            '200 OK',
            $response->getStatus()
        );

        $this->assertEquals(
            'Gir',
            $response->getHeader('X-Zim')
        );

        $this->assertEquals(
            'hi!',
            $response->getBody(Message::BODY_STRING)
        );

    }

    function testSendPUTStream() {

        $client = new ClientMock();

        $h = null;

        $client->on('curl', function($settings, &$result) use (&$h) {

            $this->assertEquals([
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_POSTREDIR => 3,
                CURLOPT_PUT => true,
                CURLOPT_INFILE => $h,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
            ], $settings);

            $returnHeaders = [
                "HTTP/1.1 200 OK",
                "X-Zim: Gir",
            ];

            $returnHeaders = implode("\r\n", $returnHeaders) . "\r\n\r\n";

            $returnBody = "hi!";

            $result = [
                $returnHeaders . $returnBody,
                [
                    'header_size' => strlen($returnHeaders),
                    'http_code' => 200,
                ],
                0,
                '',
            ];


        });

        $h = fopen('php://memory', 'r+');
        fwrite($h, 'booh');

        $request = new Request('PUT','http://example.org/', ['X-Foo' => 'bar'], $h);
        $response = $client->send($request);

        $this->assertEquals(
            '200 OK',
            $response->getStatus()
        );

        $this->assertEquals(
            'Gir',
            $response->getHeader('X-Zim')
        );

        $this->assertEquals(
            'hi!',
            $response->getBody(Message::BODY_STRING)
        );

    }

    function testSendPUTString() {

        $client = new ClientMock();

        $client->on('curl', function($settings, &$result) {

            $this->assertEquals([
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_POSTREDIR => 3,
                CURLOPT_POSTFIELDS => 'boo',
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
            ], $settings);

            $returnHeaders = [
                "HTTP/1.1 200 OK",
                "X-Zim: Gir",
            ];

            $returnHeaders = implode("\r\n", $returnHeaders) . "\r\n\r\n";

            $returnBody = "hi!";

            $result = [
                $returnHeaders . $returnBody,
                [
                    'header_size' => strlen($returnHeaders),
                    'http_code' => 200,
                ],
                0,
                '',
            ];


        });

        $request = new Request('PUT','http://example.org/', ['X-Foo' => 'bar'], 'boo');
        $response = $client->send($request);

        $this->assertEquals(
            '200 OK',
            $response->getStatus()
        );

        $this->assertEquals(
            'Gir',
            $response->getHeader('X-Zim')
        );

        $this->assertEquals(
            'hi!',
            $response->getBody(Message::BODY_STRING)
        );

    }

    /**
     * @expectedException \Sabre\HTTP\ClientException
     */
    function testCurlError() {

        $client = new ClientMock();

        $client->on('curl', function($settings, &$result) {

            $result = [
                '',
                [
                    'header_size' => 0,
                    'http_code' => 200,
                ],
                1,
                'Error',
            ];


        });

        $request = new Request('PUT','http://example.org/', ['X-Foo' => 'bar'], 'boo');
        $client->send($request);

    }

    function testSendRetryAfterError() {

        $client = new ClientMock();

        $foo = 0;

        $client->on('curl', function($settings, &$result) use (&$foo) {

            $foo++;
            if ($foo === 1) {
                $returnCode = '400 Bad request';
            } else {
                $returnCode = '200 OK';
            }

            $returnHeaders = [
                "HTTP/1.1 " . $returnCode,
                "X-Zim: Gir",
            ];

            $returnHeaders = implode("\r\n", $returnHeaders) . "\r\n\r\n";

            $returnBody = "hi!";

            $result = [
                $returnHeaders . $returnBody,
                [
                    'header_size' => strlen($returnHeaders),
                    'http_code' => (int)$returnCode,
                ],
                0,
                '',
            ];


        });

        $request = new Request('PUT','http://example.org/', ['X-Foo' => 'bar'], 'boo');
        $response = $client->send($request);

        $this->assertEquals(
            '400 Bad request',
            $response->getStatus()
        );
        $this->assertEquals(1, $foo);

        // Doing this again, but retrying this time.
        $foo = 0;
        $client->on('error:400', function($request, $response, &$retry, $retryCount) {
            if ($retryCount === 0) $retry = true;
        });

        $response = $client->send($request);

        $this->assertEquals(
            '200 OK',
            $response->getStatus()
        );
        $this->assertEquals(2, $foo);

    }
}

class ClientMock extends Client {

    function curlRequest($settings) {

        $this->emit('curl', [$settings, &$result]);
        return $result;

    }

}
