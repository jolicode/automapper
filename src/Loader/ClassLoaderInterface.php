<?php

declare(strict_types=1);

namespace AutoMapper\Loader;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\MetadataRegistry;

/**
 * Loads (require) a mapping given metadata.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 *  @internal
 */
interface ClassLoaderInterface
{
    public function loadClass(MapperMetadata $mapperMetadata): void;

    public function buildMappers(MetadataRegistry $registry): bool;
}
