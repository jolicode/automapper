<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Tests\Fixtures\User;
use AutoMapper\Tests\Fixtures\UserDTO;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;

final readonly class SourceTargetCustomPropertyTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    public function supports(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        return $mapperMetadata->source === UserDTO::class && $mapperMetadata->target === User::class && $source->property === 'name';
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        return "{$source->getName()} from custom property transformer";
    }
}
