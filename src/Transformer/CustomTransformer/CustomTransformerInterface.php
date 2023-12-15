<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\CustomTransformer;

/**
 * @experimental
 */
interface CustomTransformerInterface
{
    /**
     * @param object|array<string, mixed> $source the source input on which the custom transformation applies
     *
     * Return value depends on the custom transformer's type:
     *   - for CustomModelTransformerInterface, you should return a full target object,
     *   - for CustomPropertyTransformerInterface, only the target property should be returned
     */
    public function transform(object|array $source): mixed;
}
