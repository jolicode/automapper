<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class CircularBar
{
    /** @var CircularBaz */
    public $baz;
}
