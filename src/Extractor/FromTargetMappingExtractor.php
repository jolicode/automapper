<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Configuration;
use AutoMapper\Exception\InvalidMappingException;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

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
    private const ALLOWED_SOURCES = ['array', \stdClass::class];

    public function __construct(
        Configuration $configuration,
        PropertyInfoExtractorInterface $propertyInfoExtractor,
        PropertyReadInfoExtractorInterface $readInfoExtractor,
        PropertyWriteInfoExtractorInterface $writeInfoExtractor,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        private readonly ?AdvancedNameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($configuration, $propertyInfoExtractor, $readInfoExtractor, $writeInfoExtractor, $classMetadataFactory);
    }

    public function getPropertiesMapping(MapperMetadata $mapperMetadata): array
    {
        $targetProperties = array_unique($this->propertyInfoExtractor->getProperties($mapperMetadata->target) ?? []);

        if (!\in_array($mapperMetadata->source, self::ALLOWED_SOURCES, true)) {
            throw new InvalidMappingException('Only array or stdClass are accepted as a source');
        }

        $mapping = [];
        foreach ($targetProperties as $property) {
            if (!$this->isWritable($mapperMetadata->target, $property)) {
                continue;
            }

            $targetTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->target, $property);

            if (null === $targetTypes) {
                continue;
            }

            $sourceTypes = [];

            foreach ($targetTypes as $type) {
                $sourceType = $this->transformType($mapperMetadata->source, $type);

                if ($sourceType) {
                    $sourceTypes[] = $sourceType;
                }
            }

            $sourcePropertyName = $this->nameConverter?->denormalize($property, $mapperMetadata->target, $mapperMetadata->source) ?? $property;

            $sourceProperty = new SourcePropertyMetadata(
                types: $sourceTypes,
                name: $sourcePropertyName,
                accessor: $this->getReadAccessor($mapperMetadata->source, $mapperMetadata->target, $sourcePropertyName),
                checkExists: true,
                isPublic: true,
                dateTimeFormat: $this->configuration->dateTimeFormat,
            );

            $targetProperty = new TargetPropertyMetadata(
                $targetTypes,
                $property,
                $this->getWriteMutator($mapperMetadata->source, $mapperMetadata->target, $property, [
                    'enable_constructor_extraction' => false,
                ]),
                $this->getWriteMutator($mapperMetadata->source, $mapperMetadata->target, $property, [
                    'enable_constructor_extraction' => true,
                ]),
                $this->getGroups($mapperMetadata->source, $property),
                $this->isIgnoredProperty($mapperMetadata->source, $property),
                dateTimeFormat: $this->configuration->dateTimeFormat,
            );

            $mapping[] = new PropertyMetadata(
                $sourceProperty,
                $targetProperty,
                $this->getMaxDepth($mapperMetadata->target, $property),
            );
        }

        return $mapping;
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

        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() && (\DateTimeInterface::class === $type->getClassName() || is_subclass_of($type->getClassName(), \DateTimeInterface::class))) {
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

    /**
     * PropertyInfoExtractor::isWritable() is not enough: we want to know if the property is readonly and writable from the constructor.
     */
    private function isWritable(string $target, string $property): bool
    {
        if ($this->propertyInfoExtractor->isWritable($target, $property)) {
            return true;
        }

        $writeInfo = $this->writeInfoExtractor->getWriteInfo($target, $property, ['enable_constructor_extraction' => true]);

        if (null === $writeInfo || $writeInfo->getType() !== PropertyWriteInfo::TYPE_CONSTRUCTOR) {
            return false;
        }

        return true;
    }
}
