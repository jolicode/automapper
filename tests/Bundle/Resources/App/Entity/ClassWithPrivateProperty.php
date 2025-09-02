<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Entity;

class ClassWithPrivateProperty
{
    public function __construct(
        private string $foo,
    ) {
    }

    private function getBar(): string
    {
        return 'bar';
    }
}
