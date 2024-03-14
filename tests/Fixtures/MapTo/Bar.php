<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

class Bar
{
    public function __construct(
        public string $bar,
        public string $baz
    ) {
    }
}
