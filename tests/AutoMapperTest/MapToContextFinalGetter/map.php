<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\MapToContextFinalGetter;

use AutoMapper\Attribute\MapToContext;
use AutoMapper\MapperContext;
use AutoMapper\Tests\AutoMapperBuilder;

class Source
{
    public function __construct(
        private string $value,
    ) {
    }

    // a final public getter must still be usable with #[MapToContext]
    final public function getValue(
        #[MapToContext('prefix')] string $prefix,
    ): string {
        return "{$prefix}_{$this->value}";
    }
}

return AutoMapperBuilder::buildAutoMapper()->map(
    new Source('bar'),
    'array',
    [MapperContext::MAP_TO_ACCESSOR_PARAMETER => ['prefix' => 'foo']]
);
