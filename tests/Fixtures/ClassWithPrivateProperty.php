<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class ClassWithPrivateProperty
{
    public function __construct(
        private string $foo
    ) {
    }

    private function getBar(): string
    {
        return 'bar';
    }
}
