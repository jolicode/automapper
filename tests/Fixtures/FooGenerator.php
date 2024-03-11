<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class FooGenerator
{
    public function getGenerator(): \Generator
    {
        yield 1;
        yield 2;
        yield 3;
        yield 'foo' => 'bar';
    }

    public function getArray(): array
    {
        return [1, 2, 3];
    }
}
