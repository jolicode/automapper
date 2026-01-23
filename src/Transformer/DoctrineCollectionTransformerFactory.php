<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class DoctrineCollectionTransformerFactory implements TransformerFactoryInterface, ChainTransformerFactoryAwareInterface
{
    use ChainTransformerFactoryAwareTrait;

    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        if (!interface_exists(Collection::class)) {
            return null;
        }

        if (!$source->type instanceof Type\CollectionType || null === $target->type) {
            return null;
        }

        $isDoctrineCollection = $target->type->isSatisfiedBy(function (Type $type) {
            if (!$type instanceof Type\ObjectType) {
                return false;
            }

            $className = $type->getClassName();
            $implementedClasses = class_implements($className);

            return $className === Collection::class || ($implementedClasses && \in_array(Collection::class, $implementedClasses, true));
        });

        if ($isDoctrineCollection) {
            $sourceItemType = $source->type->getCollectionValueType();
            $targetItemType = $target->type instanceof Type\CollectionType ? $target->type->getCollectionValueType() : Type::mixed();

            $newSource = $source->withType($sourceItemType);
            $newTarget = $target->withType($targetItemType);

            $subItemTransformer = $this->chainTransformerFactory->getTransformer($newSource, $newTarget, $mapperMetadata);

            if (null === $subItemTransformer) {
                return null;
            }

            if ($subItemTransformer instanceof ObjectTransformer) {
                $subItemTransformer->deepTargetToPopulate = false;
            }

            if ($target->writeMutator?->isAdderRemover()) {
                return new ArrayTransformer($subItemTransformer);
            }

            return new ArrayToDoctrineCollectionTransformer($subItemTransformer);
        }

        return null;
    }
}
