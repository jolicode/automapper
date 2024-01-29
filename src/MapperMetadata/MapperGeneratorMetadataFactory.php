<?php

declare(strict_types=1);

namespace AutoMapper\MapperMetadata;

use AutoMapper\Extractor\FromSourceMappingExtractor;
use AutoMapper\Extractor\FromTargetMappingExtractor;
use AutoMapper\Extractor\SourceTargetMappingExtractor;
use AutoMapper\Generator\TransformerResolver\TransformerResolverInterface;

/**
 * Metadata factory, used to autoregistering new mapping without creating them.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class MapperGeneratorMetadataFactory implements MapperGeneratorMetadataFactoryInterface
{
    public function __construct(
        private SourceTargetMappingExtractor $sourceTargetPropertiesMappingExtractor,
        private FromSourceMappingExtractor $fromSourcePropertiesMappingExtractor,
        private FromTargetMappingExtractor $fromTargetPropertiesMappingExtractor,
        private string $classPrefix = 'Mapper_',
        private bool $attributeChecking = true,
        private string $dateTimeFormat = \DateTime::RFC3339,
        private bool $mapPrivateProperties = true,
    ) {
    }

    /**
     * Create metadata for a source and target.
     */
    public function create(string $source, string $target): MapperGeneratorMetadataInterface
    {
        $extractor = $this->sourceTargetPropertiesMappingExtractor;

        if ('array' === $source || 'stdClass' === $source) {
            $extractor = $this->fromTargetPropertiesMappingExtractor;
        }

        if ('array' === $target || 'stdClass' === $target) {
            $extractor = $this->fromSourcePropertiesMappingExtractor;
        }

        $mapperMetadata = new MapperMetadata($extractor, $source, $target, $this->isReadOnly($target), $this->mapPrivateProperties, $this->classPrefix);
        $mapperMetadata->setAttributeChecking($this->attributeChecking);
        $mapperMetadata->setDateTimeFormat($this->dateTimeFormat);

        return $mapperMetadata;
    }

    private function isReadOnly(string $mappedType): bool
    {
        try {
            $reflClass = new \ReflectionClass($mappedType);
        } catch (\ReflectionException $e) {
            $reflClass = null;
        }
        if (\PHP_VERSION_ID >= 80200 && null !== $reflClass && $reflClass->isReadOnly()) {
            return true;
        }

        return false;
    }
}
