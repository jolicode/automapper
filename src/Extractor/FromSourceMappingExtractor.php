<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\TypeInfo\Type;

/**
 * Mapping extracted only from source, useful when not having metadata on the target for dynamic data like array, \stdClass, ...
 *
 * Can use a NameConverter to use specific properties name in the target
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class FromSourceMappingExtractor extends MappingExtractor
{
    public function getTypes(string $source, SourcePropertyMetadata $sourceProperty, string $target, TargetPropertyMetadata $targetProperty, bool $extractTypesFromGetter): array
    {
        $sourceType = $this->sourceTypeExtractor->getType($source, $sourceProperty->property) ?? Type::mixed();
        $targetType = $this->transformSourceType($target, $sourceType);

        return [$sourceType, $targetType ?? Type::mixed()];
    }

    private function transformSourceType(string $target, ?Type $type = null): ?Type
    {
        if (null === $type) {
            return null;
        }

        if ($type instanceof Type\NullableType) {
            $wrappedType = $this->transformSourceType($target, $type->getWrappedType());

            if (null === $wrappedType) {
                return null;
            }

            return new Type\NullableType($wrappedType);
        }

        if ($type instanceof Type\UnionType) {
            $types = [];

            foreach ($type->getTypes() as $subType) {
                $transformedType = $this->transformSourceType($target, $subType);

                if ($transformedType) {
                    $types[] = $transformedType;
                }
            }

            return new Type\UnionType(...$types);
        }

        if ($type instanceof Type\IntersectionType) {
            $types = [];

            foreach ($type->getTypes() as $subType) {
                $transformedType = $this->transformSourceType($target, $subType);

                if ($transformedType) {
                    $types[] = $transformedType;
                }
            }

            return new Type\IntersectionType(...$types);
        }

        if ($type instanceof Type\CollectionType) {
            $keyType = $this->transformSourceType($target, $type->getCollectionKeyType());
            $valueType = $this->transformSourceType($target, $type->getCollectionValueType());

            return Type::array($valueType, $keyType, $type->isList());
        }

        // maybe check for collection ?
        if ($type instanceof Type\ObjectType && \Generator::class === $type->getClassName()) {
            return Type::array();
        }

        // Transform datetime to string
        if ($type instanceof Type\ObjectType && $type->getClassName() !== null && (\DateTimeInterface::class === $type->getClassName() || is_subclass_of($type->getClassName(), \DateTimeInterface::class))) {
            return Type::string();
        }

        // Transform objects to array or \stdClass given the target
        if ($type instanceof Type\ObjectType && \stdClass::class !== $type->getClassName()) {
            return $target === 'array' ? Type::arrayShape([]) : Type::object(\stdClass::class);
        }

        return $type;
    }
}
