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
 * Create a transformer to handle array shape types (array{key: type, ...}).
 *
 * Applies per-key sub-transformers instead of a uniform loop.
 *
 * @internal
 */
final class ArrayShapeTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface, ChainTransformerFactoryAwareInterface
{
    use ChainTransformerFactoryAwareTrait;

    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        $sourceType = $source->type;
        $targetType = $target->type;

        if (null === $sourceType || null === $targetType) {
            return null;
        }

        if (!$targetType instanceof Type\ArrayShapeType || [] === $targetType->getShape()) {
            return null;
        }

        $fieldTransformers = [];
        $sourceIsUntyped = isset($mapperMetadata->source)
            && \in_array($mapperMetadata->source, ['array', \stdClass::class, LazyMap::class], true);

        $sourceShape = $sourceType instanceof Type\ArrayShapeType ? $sourceType->getShape() : [];

        foreach ($targetType->getShape() as $key => $field) {
            $fieldTargetType = $field['type'];

            if ($sourceIsUntyped) {
                $fieldSourceType = $sourceShape[$key]['type'] ?? Type::mixed();
                [$fieldSourceType] = $this->overrideSourceCollectionType($fieldSourceType, $fieldTargetType);
            } else {
                $fieldSourceType = $sourceShape[$key]['type'] ?? $fieldTargetType;
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

    public function getPriority(): int
    {
        return 5;
    }
}
