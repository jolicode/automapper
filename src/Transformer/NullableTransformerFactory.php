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
final class NullableTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface, ChainTransformerFactoryAwareInterface
{
    use ChainTransformerFactoryAwareTrait;

    public function getTransformer(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        $sourceType = $types->getSourceUniqueType();

        if (null === $sourceType) {
            return null;
        }

        if (!$sourceType->isNullable()) {
            return null;
        }

        $isTargetNullable = false;
        $targetTypes = $types[$sourceType] ?? [];

        foreach ($targetTypes as $targetType) {
            if ($targetType->isNullable()) {
                $isTargetNullable = true;

                break;
            }
        }

        $newTypes = TypesMatching::fromSourceAndTargetTypes([new Type(
            $sourceType->getBuiltinType(),
            false,
            $sourceType->getClassName(),
            $sourceType->isCollection(),
            $sourceType->getCollectionKeyTypes(),
            $sourceType->getCollectionValueTypes()
        )], $targetTypes);

        $subTransformer = $this->chainTransformerFactory->getTransformer($newTypes, $source, $target, $mapperMetadata);

        if (null === $subTransformer) {
            return null;
        }

        // Remove nullable property here to avoid infinite loop
        return new NullableTransformer($subTransformer, $isTargetNullable);
    }

    public function getPriority(): int
    {
        return 64;
    }
}
