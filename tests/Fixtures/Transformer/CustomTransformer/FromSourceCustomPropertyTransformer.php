<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Tests\Fixtures\UserDTO;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformerInterface;

final readonly class FromSourceCustomPropertyTransformer implements CustomPropertyTransformerInterface
{
    public function supports(string $source, string $target, string $sourceProperty, string $targetProperty): bool
    {
        return $source === UserDTO::class && $target === 'array' && $sourceProperty === 'name';
    }

    /**
     * @param UserDTO $source
     */
    public function transform(object|array $source): mixed
    {
        return "{$source->getName()} set by custom property transformer";
    }
}
