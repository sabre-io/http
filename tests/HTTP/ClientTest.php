<?php

declare(strict_types=1);

namespace Sabre\HTTP;

final class ClientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Returns the expected curl protocol settings depending on available constants.
     *
     * @return array<int,int|string>
     */
    private function protocolSettings(): array
    {
        if (defined('CURLOPT_PROTOCOLS_STR') && defined('CURLOPT_REDIR_PROTOCOLS_STR')) {
            return [
                CURLOPT_PROTOCOLS_STR => 'http,https',
                CURLOPT_REDIR_PROTOCOLS_STR => 'http,https',
            ];
        }

        if (defined('CURLOPT_PROTOCOLS') && defined('CURLOPT_REDIR_PROTOCOLS')) {
            return [
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            ];
        }

        return [];
    }

    public function testCreateCurlSettingsArrayGET(): void
    {
        $client = new ClientMock();
        $client->addCurlSetting(CURLOPT_POSTREDIR, 0);

        $request = new Request('GET', 'http://example.org/', ['X-Foo' => 'bar']);

        $settings = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTREDIR => 0,
            CURLOPT_HTTPHEADER => ['X-Foo: bar'],
            CURLOPT_NOBODY => false,
            CURLOPT_URL => 'http://example.org/',
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
        ] + $this->protocolSettings();

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));
    }

    public function testCreateCurlSettingsHTTPHeader(): void
    {
        $client = new ClientMock();
        $header = [
            'Authorization: Bearer 12345',
        ];
        $client->addCurlSetting(CURLOPT_POSTREDIR, 0);
        $client->addCurlSetting(CURLOPT_HTTPHEADER, $header);

        $request = new Request('GET', 'http://example.org/');

        $settings = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTREDIR => 0,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer 12345'],
            CURLOPT_NOBODY => false,
            CURLOPT_URL => 'http://example.org/',
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
        ] + $this->protocolSettings();

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));
    }

    public function testCreateCurlSettingsArrayHEAD(): void
    {
        $client = new ClientMock();
        $request = new Request('HEAD', 'http://example.org/', ['X-Foo' => 'bar']);

        $settings = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_CUSTOMREQUEST => 'HEAD',
            CURLOPT_HTTPHEADER => ['X-Foo: bar'],
            CURLOPT_URL => 'http://example.org/',
            CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
        ] + $this->protocolSettings();

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));
    }

    public function testCreateCurlSettingsArrayGETAfterHEAD(): void
    {
        $client = new ClientMock();
        $request = new Request('HEAD', 'http://example.org/', ['X-Foo' => 'bar']);

        // Parsing the settings for this method, and discarding the result.
        // This will cause the client to automatically persist previous
        // settings and will help us detect problems.
        $client->createCurlSettingsArray($request);

        // This is the real request.
        $request = new Request('GET', 'http://example.org/', ['X-Foo' => 'bar']);

        $settings = [
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => ['X-Foo: bar'],
            CURLOPT_NOBODY => false,
            CURLOPT_URL => 'http://example.org/',
            CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
        ] + $this->protocolSettings();

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));
    }

    public function testCreateCurlSettingsArrayPUTStream(): void
    {
        $client = new ClientMock();

        $fileContent = 'booh';
        $h = fopen('php://memory', 'r+');
        fwrite($h, $fileContent);
        $request = new Request('PUT', 'http://example.org/', ['X-Foo' => 'bar'], $h);

        $settings = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $h,
            CURLOPT_INFILESIZE => strlen($fileContent),
            CURLOPT_NOBODY => false,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => ['X-Foo: bar'],
            CURLOPT_URL => 'http://example.org/',
            CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
        ] + $this->protocolSettings();

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));
    }

    public function testCreateCurlSettingsArrayPUTString(): void
    {
        $client = new ClientMock();
        $request = new Request('PUT', 'http://example.org/', ['X-Foo' => 'bar'], 'boo');

        $settings = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_POSTFIELDS => 'boo',
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => ['X-Foo: bar'],
            CURLOPT_URL => 'http://example.org/',
            CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
        ] + $this->protocolSettings();

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));
    }

    public function testIssue89MultiplePutInfileGivesWarning(): void
    {
        $client = new ClientMock();
        $tmpFile = tmpfile();
        $request = new Request('POST', 'http://example.org/', ['X-Foo' => 'bar'], 'body');

        $settings = $client->createCurlSettingsArray($request);
        $this->assertArrayNotHasKey(CURLOPT_PUT, $settings);
        $this->assertArrayNotHasKey(CURLOPT_INFILE, $settings);

        $request = new Request('POST', 'http://example.org/', ['X-Foo' => 'bar'], $tmpFile);

        $settings = $client->createCurlSettingsArray($request);
        $this->assertEquals(true, $settings[CURLOPT_PUT]);
        $this->assertEquals($tmpFile, $settings[CURLOPT_INFILE]);

        $request = new Request('POST', 'http://example.org/', ['X-Foo' => 'bar'], 'body');

        $settings = $client->createCurlSettingsArray($request);
        $this->assertArrayNotHasKey(CURLOPT_PUT, $settings);
        $this->assertArrayNotHasKey(CURLOPT_INFILE, $settings);
    }

    public function testSend(): void
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function ($request, &$response): void {
            $response = new Response(200);
        });

        $response = $client->send($request);

        $this->assertSame(200, $response->getStatus());
    }

    protected function getAbsoluteUrl(string $path): string|false
    {
        $baseUrl = getenv('BASEURL');
        if ($baseUrl) {
            $path = ltrim($path, '/');

            return "{$baseUrl}/{$path}";
        }

        return false;
    }

    /**
     * @group ci
     */
    public function testSendToGetLargeContent(): void
    {
        $url = $this->getAbsoluteUrl('/large.php');
        if (!$url) {
            self::markTestSkipped('Set an environment value BASEURL to continue');
        }

        // Allow the peak memory usage limit to be specified externally, if needed.
        // When running this test in different environments it may be appropriate to set a different limit.
        $maxPeakMemoryUsageEnvVariable = 'SABRE_HTTP_TEST_GET_LARGE_CONTENT_MAX_PEAK_MEMORY_USAGE';
        $maxPeakMemoryUsage = \getenv($maxPeakMemoryUsageEnvVariable);
        if (false === $maxPeakMemoryUsage) {
            $maxPeakMemoryUsage = 60 * 1024 ** 2;
        }

        $request = new Request('GET', $url);
        $client = new Client();
        $response = $client->send($request);

        $this->assertSame(200, $response->getStatus());
        $this->assertLessThan((int) $maxPeakMemoryUsage, memory_get_peak_usage(), "Hint: you can adjust the max peak memory usage allowed for this test by defining env variable {$maxPeakMemoryUsageEnvVariable} to be the desired max bytes");
    }

    /**
     * @group ci
     */
    public function testSendAsync(): void
    {
        $url = $this->getAbsoluteUrl('/foo');
        if (!$url) {
            self::markTestSkipped('Set an environment value BASEURL to continue');
        }

        $client = new Client();

        $request = new Request('GET', $url);
        $client->sendAsync($request, function (ResponseInterface $response): void {
            $this->assertEquals("foo\n", $response->getBody());
            $this->assertSame(200, $response->getStatus());
            $this->assertEquals(4, $response->getHeader('Content-Length'));
        }, function ($error) use ($request): never {
            $url = $request->getUrl();
            self::fail("Failed to GET {$url}");
        });

        $client->wait();
    }

    /**
     * @group ci
     */
    public function testSendAsynConsecutively(): void
    {
        $url = $this->getAbsoluteUrl('/foo');
        if (!$url) {
            self::markTestSkipped('Set an environment value BASEURL to continue');
        }

        $client = new Client();

        $request = new Request('GET', $url);
        $client->sendAsync($request, function (ResponseInterface $response): void {
            $this->assertEquals("foo\n", $response->getBody());
            $this->assertSame(200, $response->getStatus());
            $this->assertEquals(4, $response->getHeader('Content-Length'));
        }, function ($error) use ($request): never {
            $url = $request->getUrl();
            self::fail("Failed to get {$url}");
        });

        $url = $this->getAbsoluteUrl('/bar.php');
        $request = new Request('GET', $url);
        $client->sendAsync($request, function (ResponseInterface $response): void {
            $this->assertEquals("bar\n", $response->getBody());
            $this->assertSame(200, $response->getStatus());
            $this->assertSame('Bar', $response->getHeader('X-Test'));
        }, function ($error) use ($request): never {
            $url = $request->getUrl();
            self::fail("Failed to get {$url}");
        });

        $client->wait();
    }

    public function testSendClientError(): void
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function ($request, &$response): never {
            throw new ClientException('aaah', 1);
        });
        $called = false;
        $client->on('exception', function () use (&$called): void {
            $called = true;
        });

        try {
            $client->send($request);
            self::fail('send() should have thrown an exception');
        } catch (ClientException) {
        }

        $this->assertTrue($called);
    }

    public function testSendHttpError(): void
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function ($request, &$response): void {
            $response = new Response(404);
        });
        $called = 0;
        $client->on('error', function () use (&$called): void {
            ++$called;
        });
        $client->on('error:404', function () use (&$called): void {
            ++$called;
        });

        $client->send($request);
        $this->assertSame(2, $called);
    }

    public function testSendRetry(): void
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $called = 0;
        $client->on('doRequest', function ($request, &$response) use (&$called): void {
            ++$called;
            $response = $called < 3 ? new Response(404) : new Response(200);
        });

        $errorCalled = 0;
        $client->on('error', function ($request, $response, &$retry, $retryCount) use (&$errorCalled): void {
            ++$errorCalled;
            $retry = true;
        });

        $response = $client->send($request);
        $this->assertSame(3, $called);
        $this->assertSame(2, $errorCalled);
        $this->assertSame(200, $response->getStatus());
    }

    public function testHttpErrorException(): void
    {
        $client = new ClientMock();
        $client->setThrowExceptions(true);

        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function ($request, &$response): void {
            $response = new Response(404);
        });

        try {
            $client->send($request);
            self::fail('An exception should have been thrown');
        } catch (ClientHttpException $clientHttpException) {
            $this->assertEquals(404, $clientHttpException->getHttpStatus());
            $this->assertInstanceOf(Response::class, $clientHttpException->getResponse());
        }
    }

    public function testParseCurlResult(): void
    {
        $client = new ClientMock();
        $client->on('curlStuff', function (&$return): void {
            $return = [
                [
                    'header_size' => 33,
                    'http_code' => 200,
                ],
                0,
                '',
            ];
        });

        $body = "HTTP/1.1 200 OK\r\nHeader1:Val1\r\n\r\nFoo";
        /** @phpstan-ignore-next-line */
        $result = $client->parseCurlResult($body, 'foobar');

        $this->assertEquals(Client::STATUS_SUCCESS, $result['status']);
        $this->assertEquals(200, $result['http_code']);
        $this->assertEquals(200, $result['response']->getStatus());
        $this->assertEquals(['Header1' => ['Val1']], $result['response']->getHeaders());
        $this->assertEquals('Foo', $result['response']->getBodyAsString());
    }

    public function testParseCurlResultEmptyBody(): void
    {
        $client = new ClientMock();
        $client->on('curlStuff', function (&$return): void {
            $return = [
                [
                    'header_size' => 33,
                    'http_code' => 200,
                ],
                0,
                '',
            ];
        });

        $body = "HTTP/1.1 200 OK\r\nHeader1:Val1\r\n\r\n";
        /** @phpstan-ignore-next-line */
        $result = $client->parseCurlResult($body, 'foobar');

        $this->assertEquals(Client::STATUS_SUCCESS, $result['status']);
        $this->assertEquals(200, $result['http_code']);
        $this->assertEquals(200, $result['response']->getStatus());
        $this->assertEquals(['Header1' => ['Val1']], $result['response']->getHeaders());
        $this->assertEquals('', $result['response']->getBodyAsString());
    }

    public function testParseCurlError(): void
    {
        $client = new ClientMock();
        $client->on('curlStuff', function (&$return): void {
            $return = [
                [],
                1,
                'Curl error',
            ];
        });

        $body = "HTTP/1.1 200 OK\r\nHeader1:Val1\r\n\r\nFoo";
        /** @phpstan-ignore-next-line */
        $result = $client->parseCurlResult($body, 'foobar');

        $this->assertEquals(Client::STATUS_CURLERROR, $result['status']);
        $this->assertEquals(1, $result['curl_errno']);
        $this->assertEquals('Curl error', $result['curl_errmsg']);
    }

    public function testDoRequest(): void
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');
        $client->on('curlExec', function (&$return): void {
            $return = "HTTP/1.1 200 OK\r\nHeader1:Val1\r\n\r\nFoo";
        });
        $client->on('curlStuff', function (&$return): void {
            $return = [
                [
                    'header_size' => 33,
                    'http_code' => 200,
                ],
                0,
                '',
            ];
        });
        $response = $client->doRequest($request);
        $this->assertSame(200, $response->getStatus());
        $this->assertSame(['Header1' => ['Val1']], $response->getHeaders());
        $this->assertSame('Foo', $response->getBodyAsString());
    }

    public function testDoRequestCurlError(): void
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');
        $client->on('curlExec', function (&$return): void {
            $return = '';
        });
        $client->on('curlStuff', function (&$return): void {
            $return = [
                [],
                1,
                'Curl error',
            ];
        });

        try {
            $response = $client->doRequest($request);
            self::fail('This should have thrown an exception');
        } catch (ClientException $clientException) {
            $this->assertEquals(1, $clientException->getCode());
            $this->assertSame('Curl error', $clientException->getMessage());
        }
    }
}

class ClientMock extends Client
{
    /**
     * Making this method public.
     */
    public function receiveCurlHeader($curlHandle, string $headerLine): int
    {
        return parent::receiveCurlHeader($curlHandle, $headerLine);
    }

    /**
     * Making this method public.
     */
    public function createCurlSettingsArray(RequestInterface $request): array
    {
        return parent::createCurlSettingsArray($request);
    }

    /**
     * Making this method public.
     */
    public function parseCurlResult(string $response, $curlHandle): array
    {
        return parent::parseCurlResult($response, $curlHandle);
    }

    /**
     * This method is responsible for performing a single request.
     */
    public function doRequest(RequestInterface $request): ResponseInterface
    {
        $response = null;
        $this->emit('doRequest', [$request, &$response]);

        // If nothing modified $response, we're using the default behavior.
        if (is_null($response)) {
            return parent::doRequest($request);
        }

        /* @phpstan-ignore deadCode.unreachable */
        return $response;
    }

    /**
     * Returns a bunch of information about a curl request.
     *
     * This method exists so that it can easily be overridden and mocked.
     *
     * @param resource $curlHandle
     */
    public function curlStuff($curlHandle): array
    {
        $return = null;
        $this->emit('curlStuff', [&$return]);

        // If nothing modified $return, we're using the default behavior.
        if (is_null($return)) {
            return parent::curlStuff($curlHandle);
        }

        /* @phpstan-ignore deadCode.unreachable */
        return $return;
    }

    /**
     * Calls curl_exec.
     *
     * This method exists so that it can easily be overridden and mocked.
     *
     * @param resource $curlHandle
     */
    public function curlExec($curlHandle): string
    {
        $return = null;
        $this->emit('curlExec', [&$return]);

        // If nothing modified $return, we're using the default behavior.
        if (is_null($return)) {
            return parent::curlExec($curlHandle);
        }

        /* @phpstan-ignore deadCode.unreachable */
        return $return;
    }
}
