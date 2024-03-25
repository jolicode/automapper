<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\PropertyTransformer;

/**
 * Transform a property value during a mapping.
 *
 * @experimental
 */
interface PropertyTransformerInterface
{
    /**
     * @param mixed                       $value   the value of the property to transform, can be null if there is no way to read the data from the mapping
     * @param object|array<string, mixed> $source  the source input on which the custom transformation applies
     * @param array<string, mixed>        $context Context during mapping
     */
    public function transform(mixed $value, object|array $source, array $context): mixed;
}
