<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapTo;

class FooMapTo
{
    public function __construct(
        #[MapTo(Bar::class, name: 'bar')]
        #[MapTo(name: 'baz')]
        #[MapTo('array', transformer: self::class . '::testTransform')]
        public string $foo
    ) {
    }

    public static function testTransform($value)
    {
        return strtoupper($value);
    }
}
