<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Exception\InvalidMappingException;
use AutoMapper\MapperGeneratorMetadataInterface;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\TransformerPropertyFactoryInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

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
    private const ALLOWED_TARGETS = ['array', \stdClass::class];

    public function __construct(
        PropertyInfoExtractorInterface $propertyInfoExtractor,
        PropertyReadInfoExtractorInterface $readInfoExtractor,
        PropertyWriteInfoExtractorInterface $writeInfoExtractor,
        TransformerFactoryInterface|TransformerPropertyFactoryInterface $transformerFactory,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        private readonly ?AdvancedNameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($propertyInfoExtractor, $readInfoExtractor, $writeInfoExtractor, $transformerFactory, $classMetadataFactory);
    }

    public function getPropertiesMapping(MapperGeneratorMetadataInterface $mapperMetadata): array
    {
        $sourceProperties = $this->propertyInfoExtractor->getProperties($mapperMetadata->getSource());

        if (!\in_array($mapperMetadata->getTarget(), self::ALLOWED_TARGETS, true)) {
            throw new InvalidMappingException('Only array or stdClass are accepted as a target');
        }

        if (null === $sourceProperties) {
            return [];
        }

        $sourceProperties = array_unique($sourceProperties);
        $mapping = [];

        foreach ($sourceProperties as $property) {
            if (!$this->propertyInfoExtractor->isReadable($mapperMetadata->getSource(), $property)) {
                continue;
            }

            $sourceTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->getSource(), $property);

            if (null === $sourceTypes) {
                $sourceTypes = [new Type(Type::BUILTIN_TYPE_NULL)]; // if no types found, we force a null type
            }

            $targetTypes = [];

            foreach ($sourceTypes as $type) {
                $targetType = $this->transformType($mapperMetadata->getTarget(), $type);

                if ($targetType) {
                    $targetTypes[] = $targetType;
                }
            }

            if ($this->transformerFactory instanceof TransformerPropertyFactoryInterface) {
                $transformer = $this->transformerFactory->getPropertyTransformer($sourceTypes, $targetTypes, $mapperMetadata, $property);
            } else {
                $transformer = $this->transformerFactory->getTransformer($sourceTypes, $targetTypes, $mapperMetadata);
            }

            if (null === $transformer) {
                continue;
            }

            $mapping[] = new PropertyMapping(
                $mapperMetadata,
                $this->getReadAccessor($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property),
                $this->getWriteMutator($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property),
                null,
                $transformer,
                $property,
                false,
                $this->getGroups($mapperMetadata->getSource(), $property),
                $this->getGroups($mapperMetadata->getTarget(), $property),
                $this->getMaxDepth($mapperMetadata->getSource(), $property),
                $this->isIgnoredProperty($mapperMetadata->getSource(), $property),
                $this->isIgnoredProperty($mapperMetadata->getTarget(), $property),
                PropertyReadInfo::VISIBILITY_PUBLIC === ($this->readInfoExtractor->getReadInfo($mapperMetadata->getSource(), $property)?->getVisibility() ?? PropertyReadInfo::VISIBILITY_PUBLIC),
            );
        }

        return $mapping;
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
        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() && (\DateTimeInterface::class === $type->getClassName() || is_subclass_of($type->getClassName(), \DateTimeInterface::class))) {
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
        if (null !== $this->nameConverter) {
            $property = $this->nameConverter->normalize($property, $source, $target);
        }

        $targetMutator = new WriteMutator(WriteMutator::TYPE_ARRAY_DIMENSION, $property, false);

        if (\stdClass::class === $target) {
            $targetMutator = new WriteMutator(WriteMutator::TYPE_PROPERTY, $property, false);
        }

        return $targetMutator;
    }
}
