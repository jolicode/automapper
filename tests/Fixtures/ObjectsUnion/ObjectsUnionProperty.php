<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\ObjectsUnion;

final readonly class ObjectsUnionProperty
{
    public function __construct(
        public Foo|Bar $prop
    ) {
    }
}
