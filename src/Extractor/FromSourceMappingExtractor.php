<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Exception\InvalidMappingException;
use AutoMapper\MapperMetadata\MapperGeneratorMetadataInterface;
use AutoMapper\Transformer\TransformerFactoryInterface;
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
        TransformerFactoryInterface $transformerFactory,
        CustomTransformersRegistry $customTransformerRegistry,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        private readonly ?AdvancedNameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($propertyInfoExtractor, $readInfoExtractor, $writeInfoExtractor, $transformerFactory, $customTransformerRegistry, $classMetadataFactory);
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

            $mapping[] = new PropertyMapping(
                $mapperMetadata,
                $this->getReadAccessor($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property),
                $this->getWriteMutator($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property),
                null,
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
