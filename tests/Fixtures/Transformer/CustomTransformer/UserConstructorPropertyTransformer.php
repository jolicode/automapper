<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Tests\Fixtures\UserConstructorDTO;
use AutoMapper\Tests\Fixtures\UserDTO;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformerInterface;

final readonly class UserConstructorPropertyTransformer implements CustomPropertyTransformerInterface
{
    public function supports(string $source, string $target, string $propertyName): bool
    {
        return $source === UserDTO::class && $target === UserConstructorDTO::class && $propertyName === 'name'; 
    }

    /**
     * @param UserDTO $source
     */
    public function transform(object|array $source): mixed
    {
        return "custom {$source->getName()}";
    }
}
