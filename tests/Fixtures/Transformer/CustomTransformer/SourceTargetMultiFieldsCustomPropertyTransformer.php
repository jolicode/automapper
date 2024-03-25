<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Tests\Fixtures\BirthDateDateTime;
use AutoMapper\Tests\Fixtures\BirthDateExploded;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;

final readonly class SourceTargetMultiFieldsCustomPropertyTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        return $mapperMetadata->source === BirthDateExploded::class && $mapperMetadata->target === BirthDateDateTime::class && $target->name === 'date';
    }

    public function transform(mixed $value, array|object $source, array $context): \DateTimeImmutable
    {
        return new \DateTimeImmutable("{$source->year}-{$source->month}-{$source->day}");
    }
}
