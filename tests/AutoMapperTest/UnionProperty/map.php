<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\UnionProperty;

use AutoMapper\Tests\AutoMapperBuilder;

final readonly class Bar
{
    public function __construct(
        public string $bar
    ) {
    }
}

final readonly class Foo
{
    public function __construct(
        public string $foo
    ) {
    }
}

final readonly class ObjectsUnionProperty
{
    public function __construct(
        public Foo|Bar $prop
    ) {
    }
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield 'bar' => $autoMapper->map(new ObjectsUnionProperty(new Bar('bar')), 'array');

    yield 'foo' => $autoMapper->map(new ObjectsUnionProperty(new Foo('foo')), 'array');
})();
