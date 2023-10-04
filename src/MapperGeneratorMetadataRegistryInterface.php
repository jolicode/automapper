<?php

declare(strict_types=1);

namespace AutoMapper;

use AutoMapper\Transformer\CustomTransformer\CustomTransformerInterface;
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
     * Bind custom TransformerFactory to the AutoMapper.
     */
    public function bindTransformerFactory(TransformerFactoryInterface $transformerFactory): void;

    /**
     * Bind custom TransformerFactory to the AutoMapper.
     */
    public function bindCustomTransformer(CustomTransformerInterface $customTransformer): void;

    /**
     * Get metadata for a source and a target.
     */
    public function getMetadata(string $source, string $target): ?MapperGeneratorMetadataInterface;
}
