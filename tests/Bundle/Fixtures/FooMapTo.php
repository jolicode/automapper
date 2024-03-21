<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Fixtures;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\Fixtures\MapTo\Bar;

#[MapTo('array', name: 'transformWithExpressionFunction', transformer: "service('foo').foo()")]
class FooMapTo
{
    public function __construct(
        #[MapTo(Bar::class, name: 'bar')]
        #[MapTo(name: 'baz')]
        #[MapTo('array', name: 'transformFromIsCallable', transformer: self::class . '::transformFromIsCallable')]
        #[MapTo('array', name: 'transformFromStringInstance', transformer: 'transformFromStringInstance')]
        #[MapTo('array', name: 'transformFromStringStatic', transformer: 'transformFromStringStatic')]
        #[MapTo('array', name: 'transformFromExpressionLanguage', transformer: "source.foo === 'foo' ? 'transformed' : 'not transformed'")]
        public string $foo
    ) {
    }

    #[MapTo('array', if: 'source.foo == "foo"')]
    public string $if = 'if';

    #[MapTo('array', if: 'shouldMapStatic')]
    public string $ifCallableStatic = 'if';

    #[MapTo('array', if: 'shouldMapNotStatic')]
    public string $ifCallable = 'if';

    #[MapTo('array', if: 'is_object')]
    public string $ifCallableOther = 'if';

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

    public static function shouldMapStatic($source): bool
    {
        return $source->foo === 'foo';
    }

    public function shouldMapNotStatic(): bool
    {
        return $this->foo === 'foo';
    }

    public function transformExternalProperty($value)
    {
        return 'external';
    }
}
