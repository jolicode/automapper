<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Tests\Fixtures\CityFoo;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;

final readonly class TransformerWithDependency implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    public function __construct(
        private FooDependency $fooDependency
    ) {
    }

    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        return $mapperMetadata->source === CityFoo::class && $mapperMetadata->target === 'array' && $source->name === 'name';
    }

    public function transform(mixed $value, object|array $source, array $context): string
    {
        return $this->fooDependency->getBar();
    }
}
