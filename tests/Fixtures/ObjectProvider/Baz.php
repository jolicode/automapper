<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\ObjectProvider;

class Baz
{
    /**
     * @param Foo[] $collection
     */
    public function __construct(
        public Foo $foo,
        public array $collection,
    ) {
    }
}
