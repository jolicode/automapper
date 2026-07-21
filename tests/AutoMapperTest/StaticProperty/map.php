<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\StaticProperty;

use AutoMapper\Tests\AutoMapperBuilder;

class Source
{
    public static string $staticProperty = 'static value';

    public string $name = 'john';
}

class Target
{
    public static string $staticProperty = 'other static value';

    public string $name = '';
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    // static properties are not part of an object state, they must not be mapped
    yield 'to-array' => $autoMapper->map(new Source(), 'array');

    yield 'to-object' => $autoMapper->map(new Source(), Target::class);
})();
