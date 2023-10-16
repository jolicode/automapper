<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\MapperMetadataInterface;

/**
 * Reduce array of type to only one type on source and target.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class UniqueTypeTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    public function __construct(
        private ChainTransformerFactory $chainTransformerFactory,
    ) {
    }

    public function getTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata): ?TransformerInterface
    {
        $nbSourceTypes = $sourceTypes ? \count($sourceTypes) : 0;
        $nbTargetTypes = $targetTypes ? \count($targetTypes) : 0;

        if (null === $sourceTypes || 0 === $nbSourceTypes || $nbSourceTypes > 1) {
            return null;
        }

        if (null === $targetTypes || $nbTargetTypes <= 1) {
            return null;
        }

        foreach ($targetTypes as $targetType) {
            $transformer = $this->chainTransformerFactory->getTransformer($sourceTypes, [$targetType], $mapperMetadata);

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
