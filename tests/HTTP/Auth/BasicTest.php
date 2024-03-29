<?php

declare(strict_types=1);

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class BasicTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCredentials(): void
    {
        $request = new Request('GET', '/', [
            'Authorization' => 'Basic '.base64_encode('user:pass:bla'),
        ]);

        $basic = new Basic('Dagger', $request, new Response());

        self::assertEquals([
            'user',
            'pass:bla',
        ], $basic->getCredentials());
    }

    public function testGetInvalidCredentialsColonMissing(): void
    {
        $request = new Request('GET', '/', [
            'Authorization' => 'Basic '.base64_encode('userpass'),
        ]);

        $basic = new Basic('Dagger', $request, new Response());

        self::assertNull($basic->getCredentials());
    }

    public function testGetCredentialsNoHeader(): void
    {
        $request = new Request('GET', '/', []);
        $basic = new Basic('Dagger', $request, new Response());

        self::assertNull($basic->getCredentials());
    }

    public function testGetCredentialsNotBasic(): void
    {
        $request = new Request('GET', '/', [
            'Authorization' => 'QBasic '.base64_encode('user:pass:bla'),
        ]);
        $basic = new Basic('Dagger', $request, new Response());

        self::assertNull($basic->getCredentials());
    }

    public function testRequireLogin(): void
    {
        $response = new Response();
        $request = new Request('GET', '/');

        $basic = new Basic('Dagger', $request, $response);

        $basic->requireLogin();

        self::assertEquals('Basic realm="Dagger", charset="UTF-8"', $response->getHeader('WWW-Authenticate'));
        self::assertEquals(401, $response->getStatus());
    }
}
