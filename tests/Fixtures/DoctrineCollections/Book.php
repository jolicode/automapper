<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\DoctrineCollections;

class Book
{
    public function __construct(
        public string $name
    ) {
    }
}
