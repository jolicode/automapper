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

        // Handle array shape types — per-key transformers (skip empty shapes from object mapping)
        if ($targetType instanceof Type\ArrayShapeType && [] !== $targetType->getShape()) {
            $fieldTransformers = [];
            $sourceIsUntyped = isset($mapperMetadata->source)
                && \in_array($mapperMetadata->source, ['array', \stdClass::class, LazyMap::class], true);

            $sourceShape = $sourceType instanceof Type\ArrayShapeType ? $sourceType->getShape() : [];

            foreach ($targetType->getShape() as $key => $field) {
                $fieldTargetType = $field['type'];

                if ($sourceIsUntyped) {
                    // Use the mirrored source type from FromTargetMappingExtractor,
                    // then override to mixed only for matching builtins (to force casts).
                    $fieldSourceType = $sourceShape[$key]['type'] ?? Type::mixed();
                    [$fieldSourceType] = $this->overrideSourceCollectionType($fieldSourceType, $fieldTargetType);
                } else {
                    $fieldSourceType = $fieldTargetType;
                }

                $newSource = $source->withType($fieldSourceType);
                $newTarget = $target->withType($fieldTargetType);

                $fieldTransformer = $this->chainTransformerFactory->getTransformer($newSource, $newTarget, $mapperMetadata);

                if (null === $fieldTransformer) {
                    return null;
                }

                $fieldTransformers[$key] = [
                    'transformer' => $fieldTransformer,
                    'optional' => $field['optional'],
                ];
            }

            return new ArrayShapeTransformer($fieldTransformers);
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
                return new ArrayTransformer($subItemTransformer);
            }

            return new DictionaryTransformer($subItemTransformer);
        }

        return null;
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
