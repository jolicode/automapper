<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

readonly class ConstructorWithDefaultValues
{
    public function __construct(
        public string $baz,
        public ?int $foo = 1,
        public int $bar = 0,
        public array $someOtters = [],
    ) {
    }
}
