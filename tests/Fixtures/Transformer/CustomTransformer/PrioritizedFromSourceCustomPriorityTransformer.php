<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Tests\Fixtures\UserDTO;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformerInterface;
use AutoMapper\Transformer\CustomTransformer\PrioritizedCustomTransformerInterface;

final readonly class PrioritizedFromSourceCustomPriorityTransformer implements CustomPropertyTransformerInterface, PrioritizedCustomTransformerInterface
{
    public function supports(string $source, string $target, string $propertyName): bool
    {
        return $source === UserDTO::class && $target === 'array' && $propertyName === 'address';
    }

    /**
     * @param UserDTO $source
     */
    public function transform(object|array $source): mixed
    {
        return "address with city \"{$source->address->city}\"";
    }

    public function getPriority(): int
    {
        return 10;
    }
}
