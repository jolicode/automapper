<?php

declare(strict_types=1);

namespace AutoMapper\MapperMetadata;

use AutoMapper\CustomTransformer\CustomTransformerInterface;
use AutoMapper\Transformer\TransformerFactoryInterface;

/**
 * Registry of metadata.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
interface MapperGeneratorMetadataRegistryInterface
{
    /**
     * Register metadata.
     */
    public function register(MapperGeneratorMetadataInterface $configuration): void;

    /**
     * Get metadata for a source and a target.
     */
    public function getMetadata(string $source, string $target): ?MapperGeneratorMetadataInterface;
}
