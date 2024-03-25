<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

readonly class UserPromoted
{
    /**
     * @param array<AddressDTO> $addresses
     */
    public function __construct(
        public array $addresses
    ) {
    }
}
