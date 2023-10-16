<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\MapperMetadataInterface;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class MultipleTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    public function __construct(
        private ChainTransformerFactory $chainTransformerFactory,
    ) {
    }

    public function getTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata): ?TransformerInterface
    {
        if (null === $sourceTypes || \count($sourceTypes) <= 1) {
            return null;
        }

        $transformers = [];

        foreach ($sourceTypes as $sourceType) {
            $transformer = $this->chainTransformerFactory->getTransformer([$sourceType], $targetTypes, $mapperMetadata);

            if (null !== $transformer) {
                $transformers[] = [
                    'transformer' => $transformer,
                    'type' => $sourceType,
                ];
            }
        }

        if (\count($transformers) > 1) {
            return new MultipleTransformer($transformers);
        }

        if (\count($transformers) === 1) {
            return $transformers[0]['transformer'];
        }

        return null;
    }

    public function getPriority(): int
    {
        return 128;
    }
}
