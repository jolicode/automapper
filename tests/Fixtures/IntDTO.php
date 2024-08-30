<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

readonly class IntDTO
{
    public function __construct(
        public int $foo,
    ) {
    }
}
