<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\CustomTransformer\CustomPropertyTransformerInterface;
use AutoMapper\Tests\Fixtures\UserDTO;

final readonly class FromSourceCustomPropertyTransformer implements CustomPropertyTransformerInterface
{
    public function supports(string $source, string $target, string $propertyName): bool
    {
        return $source === UserDTO::class && $target === 'array' && $propertyName === 'name';
    }

    /**
     * @param UserDTO $source
     */
    public function transform(object|array $source): mixed
    {
        return "{$source->getName()} set by custom property transformer";
    }
}
