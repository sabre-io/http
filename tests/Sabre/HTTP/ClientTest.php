<?php

namespace Sabre\HTTP;

class ClientTest extends \PHPUnit_Framework_TestCase {

    protected $client;

    function testCreateCurlSettingsArrayGET() {

        $client = new ClientMock();
        $client->addCurlSetting(CURLOPT_POSTREDIR, 0);

        $request = new Request('GET','http://example.org/', ['X-Foo' => 'bar']);

        $this->assertEquals(
            [
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
            ],
            $client->createCurlSettingsArray($request)
        );

    }

    function testCreateCurlSettingsArrayHEAD() {

        $client = new ClientMock();
        $request = new Request('HEAD','http://example.org/', ['X-Foo' => 'bar']);

        $this->assertEquals(
            [
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
            ],
            $client->createCurlSettingsArray($request)
        );

    }

    function testSendPUTStream() {

        $client = new ClientMock();

        $h = fopen('php://memory', 'r+');
        fwrite($h, 'booh');
        $request = new Request('PUT','http://example.org/', ['X-Foo' => 'bar'], $h);

        $this->assertEquals(
            [
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
            ],
            $client->createCurlSettingsArray($request)
        );

    }

    function testSendPUTString() {

        $client = new ClientMock();
        $request = new Request('PUT','http://example.org/', ['X-Foo' => 'bar'], 'boo');

        $this->assertEquals(
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_POSTREDIR => 3,
                CURLOPT_POSTFIELDS => 'boo',
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
            ],
            $client->createCurlSettingsArray($request)
        );

    }

}

class ClientMock extends Client {

    /**
     * Making this method public.
     */
    public function createCurlSettingsArray(RequestInterface $request) {

        return parent::createCurlSettingsArray($request);

    }

}
