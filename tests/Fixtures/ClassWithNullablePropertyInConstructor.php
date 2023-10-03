<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

readonly class ClassWithNullablePropertyInConstructor
{
    public function __construct(public int $foo, public int|null $bar = null)
    {
    }
}
