<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

readonly class ConstructorWithDefaultValuesAsObjects
{
    public function __construct(
        public string $baz,
        public IntDTO $IntDTO = new IntDTO(1),
        public \DateTimeImmutable $date = new \DateTimeImmutable(),
    ) {
    }
}
