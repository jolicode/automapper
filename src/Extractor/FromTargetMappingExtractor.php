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
        $targetTypes = $this->propertyInfoExtractor->getTypes($target, $targetProperty->name, [
            ReadWriteTypeExtractor::WRITE_MUTATOR => $targetProperty->writeMutator,
        ]) ?? [];

        foreach ($targetTypes as $type) {
            $sourceType = $this->transformType($source, $type);

            if ($sourceType) {
                $types[$sourceType] = [$type];
            }
        }

        return $types;
    }

    public function getReadAccessor(string $source, string $target, string $property): ?ReadAccessor
    {
        $sourceAccessor = new ReadAccessor(ReadAccessor::TYPE_ARRAY_DIMENSION, $property);

        if (\stdClass::class === $source) {
            $sourceAccessor = new ReadAccessor(ReadAccessor::TYPE_PROPERTY, $property);
        }

        return $sourceAccessor;
    }

    private function transformType(string $source, ?Type $type = null): ?Type
    {
        if (null === $type) {
            return null;
        }

        $builtinType = $type->getBuiltinType();
        $className = $type->getClassName();

        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() && \stdClass::class !== $type->getClassName()) {
            $builtinType = 'array' === $source ? Type::BUILTIN_TYPE_ARRAY : Type::BUILTIN_TYPE_OBJECT;
            $className = 'array' === $source ? null : \stdClass::class;
        }

        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() && $type->getClassName() !== null && (\DateTimeInterface::class === $type->getClassName() || is_subclass_of($type->getClassName(), \DateTimeInterface::class))) {
            $builtinType = 'string';
        }

        $collectionKeyTypes = $type->getCollectionKeyTypes();
        $collectionValueTypes = $type->getCollectionValueTypes();

        return new Type(
            $builtinType,
            $type->isNullable(),
            $className,
            $type->isCollection(),
            $this->transformType($source, $collectionKeyTypes[0] ?? null),
            $this->transformType($source, $collectionValueTypes[0] ?? null)
        );
    }
}
