<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

class InvokableTransformer
{
    public function __construct(
        private string $value,
    ) {
    }

    public function __invoke(mixed $value, object|array $source, array $context): string
    {
        return $this->value;
    }
}
