<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapTo;

class BadMapToTransformer
{
    public function __construct(
        // Not valid because it is not static
        #[MapTo(transformer: [self::class, 'transformFoo'])]
        public string $foo,
    ) {
    }

    public function transformFoo(): string
    {
        return 'foo';
    }
}
