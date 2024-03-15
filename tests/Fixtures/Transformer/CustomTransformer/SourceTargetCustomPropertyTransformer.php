<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Tests\Fixtures\User;
use AutoMapper\Tests\Fixtures\UserDTO;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformerInterface;

final readonly class SourceTargetCustomPropertyTransformer implements CustomPropertyTransformerInterface
{
    public function supports(string $source, string $target, string $sourceProperty, string $targetProperty): bool
    {
        return $source === UserDTO::class && $target === User::class && $sourceProperty === 'name';
    }

    /**
     * @param UserDTO $source
     */
    public function transform(object|array $source): mixed
    {
        return "{$source->getName()} from custom property transformer";
    }
}
