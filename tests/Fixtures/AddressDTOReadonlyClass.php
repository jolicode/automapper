<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

readonly class AddressDTOReadonlyClass
{
    public function __construct(
        public string $city,
    ) {
    }
}
