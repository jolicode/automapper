<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Issue425;

class Foo
{
    /** @var bigint[] */
    private array $property = [];

    public function __construct(array $property)
    {
        $this->property = $property;
    }

    public function getProperty(): array
    {
        return $this->property;
    }
}
