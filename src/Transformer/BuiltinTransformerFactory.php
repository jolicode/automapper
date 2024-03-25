<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
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

    public function getTransformer(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        $sourceType = $types->getSourceUniqueType();

        if (null === $sourceType) {
            return null;
        }

        $targetTypes = $types[$sourceType] ?? [];

        if (\in_array($sourceType->getBuiltinType(), self::BUILTIN, true)) {
            return new BuiltinTransformer($sourceType, $targetTypes);
        }

        return null;
    }

    public function getPriority(): int
    {
        return 8;
    }
}
