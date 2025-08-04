<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

readonly class AddressDTOSecondReadonlyClass
{
    public function __construct(
        public string $city,
        public string $postalCode,
    ) {
    }
}
