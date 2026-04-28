<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\StringUnionProperty;

use AutoMapper\Tests\AutoMapperBuilder;

final readonly class ObjectsUnionProperty
{
    public function __construct(
        /** @var 'foo'|'bar' $prop */
        public string $prop,
    ) {
    }
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield 'bar' => $autoMapper->map(new ObjectsUnionProperty('bar'), 'array');

    yield 'foo' => $autoMapper->map(new ObjectsUnionProperty('foo'), 'array');
})();
