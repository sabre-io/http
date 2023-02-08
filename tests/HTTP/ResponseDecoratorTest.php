<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class ResponseDecoratorTest extends \PHPUnit\Framework\TestCase
{
    protected Response $inner;
    protected ResponseDecorator $outer;

    public function setUp(): void
    {
        $this->inner = new Response();
        $this->outer = new ResponseDecorator($this->inner);
    }

    public function testStatus(): void
    {
        $this->outer->setStatus(201);
        self::assertEquals(201, $this->inner->getStatus());
        self::assertEquals(201, $this->outer->getStatus());
        self::assertEquals('Created', $this->inner->getStatusText());
        self::assertEquals('Created', $this->outer->getStatusText());
    }

    public function testToString(): void
    {
        $this->inner->setStatus(201);
        $this->inner->setBody('foo');
        $this->inner->setHeader('foo', 'bar');

        self::assertEquals((string) $this->inner, (string) $this->outer);
    }
}
