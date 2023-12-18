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

    public function transform(mixed $input): mixed
    {
        return "address with city \"{$input->city}\"";
    }

    public function getPriority(): int
    {
        return 10;
    }
}
