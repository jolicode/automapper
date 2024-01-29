<?php

declare(strict_types=1);

namespace AutoMapper\MapperMetadata;

/**
 * Metadata factory, used to autoregistering new mapping without creating them.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
interface MapperGeneratorMetadataFactoryInterface
{
    public function create(string $source, string $target): MapperGeneratorMetadataInterface;
}
