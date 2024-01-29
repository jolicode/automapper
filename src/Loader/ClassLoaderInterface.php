<?php

declare(strict_types=1);

namespace AutoMapper\Loader;

use AutoMapper\Generator\MapperGenerator;
use AutoMapper\MapperMetadata\MapperGeneratorMetadataInterface;

/**
 * Loads (require) a mapping given metadata.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
interface ClassLoaderInterface
{
    public function loadClass(MapperGenerator $mapperGenerator, MapperGeneratorMetadataInterface $mapperMetadata): void;
}
