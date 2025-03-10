<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\DiscriminatorMapAndInterface;

class TypeB implements MyInterface
{
    public function __construct(
        public string $age,
    ) {
    }
}
