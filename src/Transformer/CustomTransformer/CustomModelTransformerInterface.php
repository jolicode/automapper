<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\CustomTransformer;

use AutoMapper\Metadata\TypesMatching;

/**
 * This interface should be implemented to handle custom transformations for a whole model (object or array)
 * from a given source to a given target:.
 *
 * ```php
 * final class CustomUserTransformer implements CustomModelTransformerInterface
 * {
 *     public function supports(string $source, string $target): bool
 *     {
 *         return $source === UserDTO::class && $target === User::class
 *     }
 *
 *     public function transform(mixed $input): User
 *     {
 *         assert($input instanceof UserDTO);
 *
 *         return User::fromDTO($input);
 *     }
 * }
 * ```
 *
 * @experimental Interface contract will change once https://github.com/symfony/symfony/pull/52510 will be merged.
 */
interface CustomModelTransformerInterface extends CustomTransformerInterface
{
    public function supports(TypesMatching $types): bool;
}
