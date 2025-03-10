<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\IssueParamDocBlock;

use AutoMapper\Tests\AutoMapperBuilder;

final readonly class map
{
    /**
     * @param string[] $foo
     */
    public function __construct(
        public string $bar,
        public array $foo,
    ) {
    }
}

final readonly class Foo
{
    /**
     * @param string[] $foo
     */
    public function __construct(
        public string $bar,
        public array $foo,
    ) {
    }
}

$foo = new Foo('bar', ['foo1', 'foo2']);

return AutoMapperBuilder::buildAutoMapper()->map($foo, 'array');
