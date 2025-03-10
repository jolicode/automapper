<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\CircularReferenceDeep;

use AutoMapper\Tests\AutoMapperBuilder;

class CircularBar
{
    /** @var CircularBaz */
    public $baz;
}

class CircularBaz
{
    /** @var CircularFoo */
    public $foo;
}

class CircularFoo
{
    /** @var CircularBar */
    public $bar;
}

$foo = new CircularFoo();
$bar = new CircularBar();
$baz = new CircularBaz();

$foo->bar = $bar;
$bar->baz = $baz;
$baz->foo = $foo;

return AutoMapperBuilder::buildAutoMapper()->map($foo, CircularFoo::class);
