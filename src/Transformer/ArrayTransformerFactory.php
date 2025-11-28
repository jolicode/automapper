<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Create a decorated transformer to handle array type.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class ArrayTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface, ChainTransformerFactoryAwareInterface
{
    use ChainTransformerFactoryAwareTrait;

    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        $sourceType = $source->type;
        $targetType = $target->type;

        if (null === $sourceType || null === $targetType) {
            return null;
        }

        if (!$this->isCollectionType($sourceType) || !$this->isCollectionType($targetType)) {
            return null;
        }

        $sourceCollectionType = $sourceType instanceof Type\CollectionType ? $sourceType->getCollectionValueType() : Type::mixed();
        $targetCollectionType = $targetType instanceof Type\CollectionType ? $targetType->getCollectionValueType() : Type::mixed();

        $newSource = $source->withType($sourceCollectionType);
        $newTarget = $target->withType($targetCollectionType);

        $subItemTransformer = $this->chainTransformerFactory->getTransformer($newSource, $newTarget, $mapperMetadata);

        if (null !== $subItemTransformer) {
            if ($subItemTransformer instanceof ObjectTransformer) {
                $subItemTransformer->deepTargetToPopulate = false;
            }

            $sourceCollectionKeyType = $sourceType instanceof Type\CollectionType ? $sourceType->getCollectionKeyType() : Type::mixed();

            if ($sourceCollectionKeyType instanceof Type\BuiltinType && $sourceCollectionKeyType->getTypeIdentifier() !== TypeIdentifier::INT) {
                return new DictionaryTransformer($subItemTransformer);
            }

            return new ArrayTransformer($subItemTransformer);
        }

        return null;
    }

    private function isCollectionType(Type $type): bool
    {
        if ($type instanceof Type\CollectionType) {
            return true;
        }

        if ($type instanceof Type\ObjectType && is_a($type->getClassName(), \Traversable::class, true)) {
            return true;
        }

        return false;
    }

    public function getPriority(): int
    {
        return 4;
    }
}
