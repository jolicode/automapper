<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Transformer\MapperDependency;

/**
 * Represent a dependency on a mapper (allow to inject sub mappers).
 *
 * @internal
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class Dependency
{
    public function __construct(
        public MapperDependency $mapperDependency,
        public GeneratorMetadata $metadata,
    ) {
    }
}
