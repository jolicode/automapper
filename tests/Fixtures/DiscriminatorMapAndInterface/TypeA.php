<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\DiscriminatorMapAndInterface;

class TypeA implements MyInterface
{
    public function __construct(
        public string $name,
    ) {
    }
}
