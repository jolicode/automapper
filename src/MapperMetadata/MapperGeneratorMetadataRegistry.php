<?php

declare(strict_types=1);

namespace AutoMapper\MapperMetadata;

final class MapperGeneratorMetadataRegistry implements MapperGeneratorMetadataRegistryInterface
{
    /** @var MapperGeneratorMetadataInterface[] */
    private array $metadata = [];

    public function __construct(
        private readonly ?MapperGeneratorMetadataFactoryInterface $mapperConfigurationFactory = null
    )
    {
    }

    public function register(MapperGeneratorMetadataInterface $configuration): void
    {
        $this->metadata[$configuration->getSource()][$configuration->getTarget()] = $configuration;
    }

    public function getMetadata(string $source, string $target): ?MapperGeneratorMetadataInterface
    {
        if (!isset($this->metadata[$source][$target])) {
            if (null === $this->mapperConfigurationFactory) {
                return null;
            }

            $this->register($this->mapperConfigurationFactory->create($source, $target));
        }

        return $this->metadata[$source][$target];
    }
}
