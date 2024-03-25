<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Configuration;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

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
    public function __construct(
        Configuration $configuration,
        PropertyInfoExtractorInterface $propertyInfoExtractor,
        PropertyReadInfoExtractorInterface $readInfoExtractor,
        PropertyWriteInfoExtractorInterface $writeInfoExtractor,
    ) {
        parent::__construct($configuration, $propertyInfoExtractor, $readInfoExtractor, $writeInfoExtractor);
    }

    public function getTypes(string $source, SourcePropertyMetadata $sourceProperty, string $target, TargetPropertyMetadata $targetProperty): TypesMatching
    {
        $types = new TypesMatching();
        $sourceTypes = $this->propertyInfoExtractor->getTypes($source, $sourceProperty->name, [
            ReadWriteTypeExtractor::READ_ACCESSOR => $sourceProperty->accessor,
        ]) ?? [new Type(Type::BUILTIN_TYPE_NULL)];

        foreach ($sourceTypes as $type) {
            $targetType = $this->transformType($target, $type);

            if ($targetType) {
                $types[$type] = [$targetType];
            }
        }

        return $types;
    }

    private function transformType(string $target, Type $type = null): ?Type
    {
        if (null === $type) {
            return null;
        }

        $builtinType = $type->getBuiltinType();
        $className = $type->getClassName();
        $collection = $type->isCollection();

        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() && \stdClass::class !== $type->getClassName()) {
            $builtinType = 'array' === $target ? Type::BUILTIN_TYPE_ARRAY : Type::BUILTIN_TYPE_OBJECT;
            $className = 'array' === $target ? null : \stdClass::class;
        }

        // Use array for generator
        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() && \Generator::class === $type->getClassName()) {
            $builtinType = Type::BUILTIN_TYPE_ARRAY;
            $className = null;
            $collection = true;
        }

        // Use string for datetime
        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() && $type->getClassName() !== null && (\DateTimeInterface::class === $type->getClassName() || is_subclass_of($type->getClassName(), \DateTimeInterface::class))) {
            $builtinType = 'string';
        }

        $collectionKeyTypes = $type->getCollectionKeyTypes();
        $collectionValueTypes = $type->getCollectionValueTypes();

        return new Type(
            $builtinType,
            $type->isNullable(),
            $className,
            $collection,
            $this->transformType($target, $collectionKeyTypes[0] ?? null),
            $this->transformType($target, $collectionValueTypes[0] ?? null)
        );
    }

    public function getWriteMutator(string $source, string $target, string $property, array $context = []): WriteMutator
    {
        $targetMutator = new WriteMutator(WriteMutator::TYPE_ARRAY_DIMENSION, $property, false);

        if (\stdClass::class === $target) {
            $targetMutator = new WriteMutator(WriteMutator::TYPE_PROPERTY, $property, false);
        }

        return $targetMutator;
    }
}
