<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class MultipleTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface, ChainTransformerFactoryAwareInterface
{
    use ChainTransformerFactoryAwareTrait;

    public function getTransformer(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        if (\count($types) <= 1) {
            return null;
        }

        $transformers = [];

        foreach ($types as $sourceType) {
            $targetTypes = $types[$sourceType] ?? [];
            $transformer = $this->chainTransformerFactory->getTransformer(TypesMatching::fromSourceAndTargetTypes([$sourceType], $targetTypes), $source, $target, $mapperMetadata);

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
