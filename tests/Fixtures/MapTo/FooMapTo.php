<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\TransformerWithDependency;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[MapTo('array', property: 'externalProperty', transformer: 'transformExternalProperty', groups: ['group1'])]
#[MapTo('array', property: 'transformWithExpressionFunction', transformer: "transformerWithDependency().transform('foo', source, context)")]
class FooMapTo
{
    public function __construct(
        #[MapTo(Bar::class, property: 'bar')]
        #[MapTo(property: 'baz')]
        #[MapTo('array', property: 'transformFromIsCallable', transformer: self::class . '::transformFromIsCallable')]
        #[MapTo('array', property: 'transformFromStringInstance', transformer: 'transformFromStringInstance')]
        #[MapTo('array', property: 'transformFromStringStatic', transformer: 'transformFromStringStatic')]
        #[MapTo('array', property: 'transformFromCustomTransformerService', transformer: TransformerWithDependency::class)]
        #[MapTo('array', property: 'transformFromExpressionLanguage', transformer: "source.foo === 'foo' ? 'transformed' : 'not transformed'")]
        #[MapTo('array', property: 'foo')]
        #[MapTo('array', property: 'fooInt', targetPropertyType: new Type\BuiltinType(TypeIdentifier::INT))]
        #[MapTo('array', property: 'fooFloat', targetPropertyType: 'float')]
        public string $foo,
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
