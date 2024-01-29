<?php

declare(strict_types=1);

namespace AutoMapper\Generator\Shared;

use AutoMapper\MapperMetadata\MapperGeneratorMetadataInterface;
use AutoMapper\MapperMetadata\MapperGeneratorMetadataRegistryInterface;
use AutoMapper\Transformer\DependentTransformerInterface;

final readonly class CircularReferenceChecker
{
    public function __construct(
        private MapperGeneratorMetadataRegistryInterface $metadataRegistry,
    ) {
    }

    public function canHaveCircularReference(MapperGeneratorMetadataInterface $mapperMetadata): bool
    {
        if ('array' === $mapperMetadata->getSource()) {
            return false;
        }

        $checked = [];

        return $this->checkCircularMapperConfiguration(
            $mapperMetadata->getSource(),
            $mapperMetadata->getTarget(),
            $mapperMetadata,
            $checked
        );
    }

    private function checkCircularMapperConfiguration(
        string $source,
        string $target,
        MapperGeneratorMetadataInterface $configuration,
        &$checked
    ): bool {
        foreach ($configuration->getPropertiesMapping() as $propertyMapping) {
            $transformer = $propertyMapping->getTransformer();

            if (!$transformer instanceof DependentTransformerInterface) {
                continue;
            }

            foreach ($transformer->getDependencies() as $dependency) {
                if (isset($checked[$dependency->name])) {
                    continue;
                }

                $checked[$dependency->name] = true;

                if ($dependency->source === $source && $dependency->target === $target) {
                    return true;
                }

                $subConfiguration = $this->metadataRegistry->getMetadata($dependency->source, $dependency->target);

                if (!$subConfiguration) {
                    continue;
                }

                if (true === $this->checkCircularMapperConfiguration($source, $target, $subConfiguration, $checked)) {
                    return true;
                }
            }
        }

        return false;
    }
}
