<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class BuiltinClass
{
    public function __construct(public \DateInterval $dateInterval)
    {
    }
}
