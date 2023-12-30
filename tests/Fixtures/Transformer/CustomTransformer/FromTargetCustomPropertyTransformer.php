<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Tests\Fixtures\UserDTO;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformerInterface;

final readonly class FromTargetCustomPropertyTransformer implements CustomPropertyTransformerInterface
{
    public function supports(string $source, string $target, string $propertyName): bool
    {
        return $source === 'array' && $target === UserDTO::class && $propertyName === 'name';
    }

    /**
     * @param array $source
     */
    public function transform(object|array $source): mixed
    {
        return "{$source['name']} from custom property transformer";
    }
}
