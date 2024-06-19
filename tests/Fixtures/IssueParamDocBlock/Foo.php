<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\IssueParamDocBlock;

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
