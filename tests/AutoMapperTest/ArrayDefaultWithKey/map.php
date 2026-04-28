<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArrayDefaultWithKey;

use AutoMapper\Tests\AutoMapperBuilder;

class Foo
{
    public function __construct(
        public array $data,
    ) {
    }
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $foo = new Foo([
        'foo' => 'foo',
    ]);

    yield 'array' => $autoMapper->map($foo, 'array');
})();
