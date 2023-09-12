<?php

declare(strict_types=1);

namespace AutoMapper\Loader;

use AutoMapper\MapperGeneratorMetadataInterface;

/**
 * Loads (require) a mapping given metadata.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
interface ClassLoaderInterface
{
    public function loadClass(MapperGeneratorMetadataInterface $mapperMetadata): void;
}
