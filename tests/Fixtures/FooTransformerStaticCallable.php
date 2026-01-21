<?php

namespace AutoMapper\Tests\Fixtures;

use AutoMapper\Attribute\MapTo;

class FooTransformerStaticCallable
{
    #[MapTo(Bar::class, transformer: static function () { return new Bar(); })]
    #[MapTo(target: 'array', transformer: static function () { return 'bar'; })]
    public string $foo;
}