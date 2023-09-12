<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class CircularFoo
{
    /** @var CircularBar */
    public $bar;
}
