<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\ObjectsUnion;

final readonly class Bar
{
    public function __construct(
        public string $bar
    ) {
    }
}
