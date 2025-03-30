<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArrayAccess;

use AutoMapper\Attribute\Mapper;
use AutoMapper\Tests\AutoMapperBuilder;

class LikeArray extends \ArrayObject
{
}

#[Mapper(source: LikeArray::class, target: LikeArray::class, allowExtraProperties: true)]
class Foo
{
    public string $foo = 'foo';
    public int $bar = 2;
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield $autoMapper->map(new LikeArray(['foo' => 'foofoo', 'bar' => 10]), Foo::class);
    yield $autoMapper->map(new Foo(), LikeArray::class);
})();
