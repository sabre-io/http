<?php

declare(strict_types=1);

namespace Sabre\HTTP;

final class ResponseDecoratorTest extends \PHPUnit\Framework\TestCase
{
    private Response $inner;

    private ResponseDecorator $outer;

    protected function setUp(): void
    {
        $this->inner = new Response();
        $this->outer = new ResponseDecorator($this->inner);
    }

    public function testStatus(): void
    {
        $this->outer->setStatus(201);
        $this->assertSame(201, $this->inner->getStatus());
        $this->assertSame(201, $this->outer->getStatus());
        $this->assertSame('Created', $this->inner->getStatusText());
        $this->assertSame('Created', $this->outer->getStatusText());
    }

    public function testToString(): void
    {
        $this->inner->setStatus(201);
        $this->inner->setBody('foo');
        $this->inner->setHeader('foo', 'bar');

        $this->assertSame((string) $this->inner, (string) $this->outer);
    }
}
