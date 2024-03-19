<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;

/**
 * Reduce array of type to only one type on source and target.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class UniqueTypeTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface, ChainTransformerFactoryAwareInterface
{
    use ChainTransformerFactoryAwareTrait;

    public function getTransformer(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        $sourceType = $types->getSourceUniqueType();

        if (null === $sourceType) {
            return null;
        }

        $targetTypes = $types[$sourceType] ?? [];

        if (\count($targetTypes) <= 1) {
            return null;
        }

        foreach ($targetTypes as $targetType) {
            $transformer = $this->chainTransformerFactory->getTransformer(TypesMatching::fromSourceAndTargetTypes([$sourceType], [$targetType]), $source, $target, $mapperMetadata);

            if (null !== $transformer) {
                return $transformer;
            }
        }

        return null;
    }

    public function getPriority(): int
    {
        return 32;
    }
}
