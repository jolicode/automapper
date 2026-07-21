<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class MultipleTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface, ChainTransformerFactoryAwareInterface
{
    use ChainTransformerFactoryAwareTrait;

    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        if (!$source->type instanceof UnionType) {
            return null;
        }

        $transformers = [];
        $sourceTypesCount = 0;

        foreach ($source->type->getTypes() as $sourceType) {
            ++$sourceTypesCount;
            $newSource = $source->withType($sourceType);
            $transformer = $this->chainTransformerFactory->getTransformer($newSource, $target, $mapperMetadata);

            if (null !== $transformer) {
                $transformers[] = [
                    'transformer' => $transformer,
                    'type' => $sourceType,
                ];
            }
        }

        // As soon as some union members have no transformer, a runtime type guard is required so the found
        // transformer only runs on the type it was built for, otherwise a value of an unhandled member would
        // be blindly transformed. MultipleTransformer generates that guard.
        if (\count($transformers) > 1 || (\count($transformers) === 1 && \count($transformers) < $sourceTypesCount)) {
            return new MultipleTransformer($transformers);
        }

        if (\count($transformers) === 1) {
            return $transformers[0]['transformer'];
        }

        return null;
    }

    public function getPriority(): int
    {
        return 64;
    }
}
