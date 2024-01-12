<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\CustomTransformer\CustomPropertyTransformerInterface;
use AutoMapper\Tests\Fixtures\BirthDateDateTime;
use AutoMapper\Tests\Fixtures\BirthDateExploded;

final readonly class SourceTargetMultiFieldsCustomPropertyTransformer implements CustomPropertyTransformerInterface
{
    public function supports(string $source, string $target, string $propertyName): bool
    {
        return $source === BirthDateExploded::class && $target === BirthDateDateTime::class && $propertyName === 'date';
    }

    /**
     * @param BirthDateExploded $source
     */
    public function transform(mixed $source): \DateTimeImmutable
    {
        return new \DateTimeImmutable("{$source->year}-{$source->month}-{$source->day}");
    }
}
