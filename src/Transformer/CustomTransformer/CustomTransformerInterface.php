<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\CustomTransformer;

/**
 * @experimental
 */
interface CustomTransformerInterface
{
    /**
     * @param mixed $input the source input (object or array) on which the custom transformation applies
     *
     * Return value depends on the custom transformer's type:
     * For CustomModelTransformerInterface, you should return a full target object,
     * for CustomPropertyTransformerInterface, only the target property should be returned
     */
    public function transform(mixed $input): mixed;
}
