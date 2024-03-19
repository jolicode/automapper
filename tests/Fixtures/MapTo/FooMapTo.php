<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\TransformerWithDependency;

class FooMapTo
{
    public function __construct(
        #[MapTo(Bar::class, name: 'bar')]
        #[MapTo(name: 'baz')]
        #[MapTo('array', name: 'transformFromIsCallable', transformer: self::class . '::transformFromIsCallable')]
        #[MapTo('array', name: 'transformFromStringInstance', transformer: 'transformFromStringInstance')]
        #[MapTo('array', name: 'transformFromStringStatic', transformer: 'transformFromStringStatic')]
        #[MapTo('array', name: 'transformFromCustomTransformerService', transformer: TransformerWithDependency::class)]
        public string $foo
    ) {
    }

    #[MapTo('array', if: 'source.foo == "foo"')]
    public string $if = 'if';

    #[MapTo('array', ignore: true)]
    public function getA(): string
    {
        return 'a';
    }

    public function getD(): string
    {
        return 'd';
    }

    public static function transformFromIsCallable($value)
    {
        return 'transformFromIsCallable_' . $value;
    }

    public function transformFromStringInstance($value)
    {
        return 'transformFromStringInstance_' . $value;
    }

    public static function transformFromStringStatic($value)
    {
        return 'transformFromStringStatic_' . $value;
    }
}
