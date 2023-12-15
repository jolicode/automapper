<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

final class BirthDateExploded
{
    public function __construct(
        public int $year,
        public int $month,
        public int $day,
    ) {
    }
}
