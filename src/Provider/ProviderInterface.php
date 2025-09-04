<?php

declare(strict_types=1);

namespace AutoMapper\Provider;

/**
 * @experimental
 *
 * @phpstan-import-type MapperContextArray from \AutoMapper\MapperContext
 */
interface ProviderInterface
{
    /**
     * Provides data for a target property.
     *
     * @param class-string<object>|'array' $targetType the target type
     * @param mixed                        $source     the source value)
     * @param MapperContextArray           $context    the context
     *
     * @return object|array<mixed>|null the value to provide
     */
    public function provide(string $targetType, mixed $source, array $context, /* mixed $id */): object|array|null;
}
