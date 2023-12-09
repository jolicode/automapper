<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\MapperMetadataInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Abstract transformer which is used by transformer needing transforming only from one single type to one single type.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
abstract class AbstractUniqueTypeTransformerFactory implements TransformerFactoryInterface
{
    public function getTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata): ?TransformerInterface
    {
        if (0 === \count($sourceTypes ?? []) || \count($sourceTypes) > 1 || !$sourceTypes[0] instanceof Type) {
            return null;
        }

        if (0 === \count($targetTypes ?? []) || \count($targetTypes) > 1 || !$targetTypes[0] instanceof Type) {
            return null;
        }

        return $this->createTransformer($sourceTypes[0], $targetTypes[0], $mapperMetadata);
    }

    abstract protected function createTransformer(Type $sourceType, Type $targetType, MapperMetadataInterface $mapperMetadata): ?TransformerInterface;
}
