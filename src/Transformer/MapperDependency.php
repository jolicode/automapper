<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

/**
 * Represent a dependency on a mapper (allow to inject sub mappers).
 *
 * @internal
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class MapperDependency
{
    /**
     * @param class-string<object>|'array'|'json' $source
     * @param class-string<object>|'array'|'json' $target
     */
    public function __construct(
        public string $name,
        public string $source,
        public string $target,
        public ?string $type = null,
    ) {
    }
}
