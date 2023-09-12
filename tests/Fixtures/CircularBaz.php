<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class CircularBaz
{
    /** @var CircularFoo */
    public $foo;
}
