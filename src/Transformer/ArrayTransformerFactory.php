<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Lazy\LazyMap;
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

        // For untyped sources, the value type is mirrored from target and needs
        // overriding to mixed so the chain generates proper scalar casts.
        $wrapWithNullable = false;
        if (isset($mapperMetadata->source)
            && \in_array($mapperMetadata->source, ['array', \stdClass::class, LazyMap::class], true)) {
            [$sourceCollectionType, $wrapWithNullable] = $this->overrideSourceCollectionType($sourceCollectionType, $targetCollectionType);
        }

        $newSource = $source->withType($sourceCollectionType);
        $newTarget = $target->withType($wrapWithNullable && $targetCollectionType instanceof Type\NullableType ? $targetCollectionType->getWrappedType() : $targetCollectionType);

        $subItemTransformer = $this->chainTransformerFactory->getTransformer($newSource, $newTarget, $mapperMetadata);

        // NullableType(mixed) is impossible in TypeInfo, so we wrap manually.
        if (null !== $subItemTransformer && $wrapWithNullable) {
            $subItemTransformer = new NullableTransformer($subItemTransformer, $targetCollectionType->isNullable());
        }

        if (null !== $subItemTransformer) {
            if ($subItemTransformer instanceof ObjectTransformer) {
                $subItemTransformer->deepTargetToPopulate = false;
            }

            $sourceCollectionKeyType = $sourceType instanceof Type\CollectionType ? $sourceType->getCollectionKeyType() : Type::mixed();

            if ($sourceCollectionKeyType instanceof Type\BuiltinType && $sourceCollectionKeyType->getTypeIdentifier() === TypeIdentifier::INT) {
                $collectionTransformer = new ArrayTransformer($subItemTransformer);
            } else {
                $collectionTransformer = new DictionaryTransformer($subItemTransformer);
            }

            if ($this->targetCanHoldLazyCollection($targetType, $mapperMetadata)) {
                return new LazyCollectionTransformer($collectionTransformer, $subItemTransformer);
            }

            return $collectionTransformer;
        }

        return null;
    }

    /**
     * Whether the target can store a {@see \AutoMapper\Lazy\LazyCollection} instead of a plain
     * array: either the target is an array shape (any value is accepted), or the property is typed
     * as a non-array iterable. A concrete `array`/`list`/`dict` property cannot, and must stay eager.
     */
    private function targetCanHoldLazyCollection(Type $targetType, MapperMetadata $mapperMetadata): bool
    {
        if (isset($mapperMetadata->target)
            && \in_array($mapperMetadata->target, ['array', \stdClass::class, LazyMap::class], true)) {
            return true;
        }

        if ($targetType instanceof Type\NullableType) {
            $targetType = $targetType->getWrappedType();
        }

        if (!$targetType instanceof Type\CollectionType) {
            return false;
        }

        $wrappedType = $targetType->getWrappedType();

        while ($wrappedType instanceof Type\GenericType) {
            $wrappedType = $wrappedType->getWrappedType();
        }

        return $wrappedType instanceof Type\BuiltinType && $wrappedType->getTypeIdentifier() === TypeIdentifier::ITERABLE;
    }

    /**
     * @return array{Type, bool} Overridden source type and whether to wrap with NullableTransformer
     */
    private function overrideSourceCollectionType(Type $sourceCollectionType, Type $targetCollectionType): array
    {
        if ($sourceCollectionType instanceof Type\NullableType) {
            $isNullable = true;
            $unwrappedSource = $sourceCollectionType->getWrappedType();
        } else {
            $isNullable = false;
            $unwrappedSource = $sourceCollectionType;
        }
        $unwrappedTarget = $targetCollectionType instanceof Type\NullableType ? $targetCollectionType->getWrappedType() : $targetCollectionType;

        if ($unwrappedSource instanceof Type\BuiltinType
            && $unwrappedTarget instanceof Type\BuiltinType
            && $unwrappedSource->getTypeIdentifier() === $unwrappedTarget->getTypeIdentifier()
            && $unwrappedSource->getTypeIdentifier() !== TypeIdentifier::MIXED) {
            return [Type::mixed(), $isNullable];
        }

        return [$sourceCollectionType, false];
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
