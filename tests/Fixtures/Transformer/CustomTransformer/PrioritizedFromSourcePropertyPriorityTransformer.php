<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Tests\Fixtures\UserDTO;
use AutoMapper\Transformer\PropertyTransformer\PrioritizedPropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;

final readonly class PrioritizedFromSourcePropertyPriorityTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface, PrioritizedPropertyTransformerInterface
{
    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        return $mapperMetadata->source === UserDTO::class && $mapperMetadata->target === 'array' && $source->name === 'address';
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        return "address with city \"{$source->address->city}\"";
    }

    public function getPriority(): int
    {
        return 10;
    }
}
