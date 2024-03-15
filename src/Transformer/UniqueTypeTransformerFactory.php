<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;

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

    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        $sourceTypes = $source->types;
        $targetTypes = $target->types;

        $nbSourceTypes = \count($sourceTypes);
        $nbTargetTypes = \count($targetTypes);

        if (0 === $nbSourceTypes || $nbSourceTypes > 1) {
            return null;
        }

        if ($nbTargetTypes <= 1) {
            return null;
        }

        foreach ($targetTypes as $targetType) {
            $transformer = $this->chainTransformerFactory->getTransformer($source, $target->withTypes([$targetType]), $mapperMetadata);

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
