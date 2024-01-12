<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

final class BirthDateDateTime
{
    public function __construct(
        public \DateTimeImmutable $date,
    ) {
    }
}
