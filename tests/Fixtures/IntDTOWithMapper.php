<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

use AutoMapper\Attribute\Mapper;

#[Mapper(strictTypes: true)]
readonly class IntDTOWithMapper
{
    public function __construct(
        public int $foo,
    ) {
    }
}
