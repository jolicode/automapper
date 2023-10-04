<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\CustomTransformer;

/**
 * This interface should be implemented to handle custom transformations for specific property,
 * from a given source type to a given target type:.
 *
 * ```php
 * final class UppercaseUserNameCustomTransformer implements CustomModelTransformerInterface
 * {
 *     public function supports(string $source, string $target, string $propertyName): bool
 *     {
 *         return $source === UserDTO::class && $target === User::class && $propertyName === 'name'
 *     }
 *
 *     public function transform(mixed $input): string
 *     {
 *        assert($input instanceof UserDTO);
 *
 *         return strtoupper($input->name);
 *     }
 * }
 * ```
 *
 * @experimental Interface contract will change once https://github.com/symfony/symfony/pull/52510 will be merged.
 */
interface CustomPropertyTransformerInterface extends CustomTransformerInterface
{
    /**
     * @param 'array'|class-string $source
     * @param 'array'|class-string $target
     */
    public function supports(string $source, string $target, string $propertyName): bool;
}
