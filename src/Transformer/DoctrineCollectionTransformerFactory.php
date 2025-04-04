<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\WriteMutator;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class DoctrineCollectionTransformerFactory extends AbstractUniqueTypeTransformerFactory implements ChainTransformerFactoryAwareInterface
{
    use ChainTransformerFactoryAwareTrait;

    protected function createTransformer(Type $sourceType, Type $targetType, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        if (!interface_exists(Collection::class)) {
            return null;
        }

        if (Type::BUILTIN_TYPE_OBJECT !== $targetType->getBuiltinType() || !\is_string($targetType->getClassName())) {
            return null;
        }

        if (Collection::class === $targetType->getClassName() || (false !== ($classImplements = class_implements($targetType->getClassName())) && \in_array(Collection::class, $classImplements))) {
            $types = TypesMatching::fromSourceAndTargetTypes($sourceType->getCollectionValueTypes(), $targetType->getCollectionValueTypes());

            $subItemTransformer = $this->chainTransformerFactory->getTransformer($types, $source, $target, $mapperMetadata);
            if (null === $subItemTransformer) {
                return null;
            }

            if ($subItemTransformer instanceof ObjectTransformer) {
                $subItemTransformer->deepTargetToPopulate = false;
            }

            if ($target->writeMutator?->type === WriteMutator::TYPE_ADDER_AND_REMOVER) {
                return new ArrayTransformer($subItemTransformer);
            }

            return new ArrayToDoctrineCollectionTransformer($subItemTransformer);
        }

        return null;
    }
}
