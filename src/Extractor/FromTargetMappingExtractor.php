<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Lazy\LazyMap;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\TypeInfo\Type;

/**
 * Mapping extracted only from target, useful when not having metadata on the source for dynamic data like array, \stdClass, ...
 *
 * Can use a NameConverter to use specific properties name in the source
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class FromTargetMappingExtractor extends MappingExtractor
{
    /**
     * @param 'array'      $source
     * @param class-string $target
     */
    public function getTypes(string $source, SourcePropertyMetadata $sourceProperty, string $target, TargetPropertyMetadata $targetProperty, bool $extractTypesFromGetter): array
    {
        $targetType = $this->targetTypeExtractor->getType($target, $targetProperty->property) ?? Type::mixed();
        $sourceType = $this->transformTargetType($source, $targetType);

        return [$sourceType ?? Type::mixed(), $targetType];
    }

    private function transformTargetType(string $source, ?Type $type = null): ?Type
    {
        if (null === $type) {
            return null;
        }

        if ($type instanceof Type\NullableType) {
            $wrappedType = $this->transformTargetType($source, $type->getWrappedType());

            if (null === $wrappedType) {
                return null;
            }

            return new Type\NullableType($wrappedType);
        }

        if ($type instanceof Type\UnionType) {
            $types = [];

            foreach ($type->getTypes() as $subType) {
                $transformedType = $this->transformTargetType($source, $subType);

                if ($transformedType) {
                    $types[] = $transformedType;
                }
            }

            return new Type\UnionType(...$types);
        }

        if ($type instanceof Type\IntersectionType) {
            $types = [];

            foreach ($type->getTypes() as $subType) {
                $transformedType = $this->transformTargetType($source, $subType);

                if ($transformedType) {
                    $types[] = $transformedType;
                }
            }

            return new Type\IntersectionType(...$types);
        }

        if ($type instanceof Type\CollectionType) {
            $keyType = $this->transformTargetType($source, $type->getCollectionKeyType());
            $valueType = $this->transformTargetType($source, $type->getCollectionValueType());

            return Type::array($valueType, $keyType, $type->isList());
        }

        // Transform datetime to string
        if ($type instanceof Type\ObjectType && $type->getClassName() !== null && (\DateTimeInterface::class === $type->getClassName() || is_subclass_of($type->getClassName(), \DateTimeInterface::class))) {
            return Type::string();
        }

        // Transform objects to array or \stdClass given the target
        if ($type instanceof Type\ObjectType && \stdClass::class !== $type->getClassName()) {
            if ($source === 'array') {
                return Type::arrayShape([]);
            }

            if ($source === \stdClass::class) {
                return Type::object(\stdClass::class);
            }

            return Type::object(LazyMap::class);
        }

        return $type;
    }
}
