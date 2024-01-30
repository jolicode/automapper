<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\MapperMetadata\MapperMetadataInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final class BuiltinTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    private const BUILTIN = [
        Type::BUILTIN_TYPE_BOOL,
        Type::BUILTIN_TYPE_CALLABLE,
        Type::BUILTIN_TYPE_FLOAT,
        Type::BUILTIN_TYPE_INT,
        Type::BUILTIN_TYPE_ITERABLE,
        Type::BUILTIN_TYPE_NULL,
        Type::BUILTIN_TYPE_RESOURCE,
        Type::BUILTIN_TYPE_STRING,
    ];

    public function getTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata): ?TransformerInterface
    {
        $nbSourceTypes = $sourceTypes ? \count($sourceTypes) : 0;

        if (null === $sourceTypes || 0 === $nbSourceTypes || $nbSourceTypes > 1 || !$sourceTypes[0] instanceof Type) {
            return null;
        }

        $propertyType = $sourceTypes[0];

        if (null !== $targetTypes && \in_array($propertyType->getBuiltinType(), self::BUILTIN, true)) {
            return new BuiltinTransformer($propertyType, $targetTypes);
        }

        return null;
    }

    public function getPriority(): int
    {
        return 8;
    }
}
