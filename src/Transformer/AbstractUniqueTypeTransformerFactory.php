<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use Symfony\Component\PropertyInfo\Type;

/**
 * Abstract transformer which is used by transformer needing transforming only from one single type to one single type.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
abstract class AbstractUniqueTypeTransformerFactory implements TransformerFactoryInterface
{
    public function getTransformer(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        $sourceType = $types->getSourceUniqueType();

        if (null === $sourceType) {
            return null;
        }

        $targetType = $types->getTargetUniqueType($sourceType);

        if (null === $targetType) {
            return null;
        }

        return $this->createTransformer($sourceType, $targetType, $source, $target, $mapperMetadata);
    }

    abstract protected function createTransformer(Type $sourceType, Type $targetType, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface;
}
