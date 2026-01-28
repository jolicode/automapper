<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\TransformerWithComputedValue;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Tests\AutoMapperBuilder;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerComputeInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;

class FooDto
{
    public string $foo;
}

class Foo
{
    public string $foo;
}

class ComputeValueTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface, PropertyTransformerComputeInterface
{
    public function transform(mixed $value, object|array $source, array $context, mixed $computed = null): mixed
    {
        return $computed ?? $value;
    }

    public function supports(
        SourcePropertyMetadata $source,
        TargetPropertyMetadata $target,
        MapperMetadata $mapperMetadata,
    ): bool {
        return true;
    }

    public function compute(
        SourcePropertyMetadata $source,
        TargetPropertyMetadata $target,
        MapperMetadata $mapperMetadata,
    ): mixed {
        return 'computed value';
    }
}

$fooDto = new FooDto();
$fooDto->foo = 'original value';

$mapper = AutoMapperBuilder::buildAutoMapper(
    propertyTransformers: [new ComputeValueTransformer()]
);

return $mapper->map(
    $fooDto,
    Foo::class,
);
