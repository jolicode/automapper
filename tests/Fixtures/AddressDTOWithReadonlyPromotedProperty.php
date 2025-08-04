<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

final class AddressDTOWithReadonlyPromotedProperty
{
    public function __construct(
        public readonly string $city,
    ) {
    }
}
