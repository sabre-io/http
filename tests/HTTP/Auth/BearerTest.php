<?php

declare(strict_types=1);

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class BearerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetToken(): void
    {
        $request = new Request('GET', '/', [
            'Authorization' => 'Bearer 12345',
        ]);

        $bearer = new Bearer('Dagger', $request, new Response());

        self::assertEquals(
            '12345',
            $bearer->getToken()
        );
    }

    public function testGetCredentialsNoHeader(): void
    {
        $request = new Request('GET', '/', []);
        $bearer = new Bearer('Dagger', $request, new Response());

        self::assertNull($bearer->getToken());
    }

    public function testGetCredentialsNotBearer(): void
    {
        $request = new Request('GET', '/', [
            'Authorization' => 'QBearer 12345',
        ]);
        $bearer = new Bearer('Dagger', $request, new Response());

        self::assertNull($bearer->getToken());
    }

    public function testRequireLogin(): void
    {
        $response = new Response();
        $request = new Request('GET', '/');
        $bearer = new Bearer('Dagger', $request, $response);

        $bearer->requireLogin();

        self::assertEquals('Bearer realm="Dagger"', $response->getHeader('WWW-Authenticate'));
        self::assertEquals(401, $response->getStatus());
    }
}
