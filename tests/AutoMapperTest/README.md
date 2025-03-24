# Automapper Test

This folder contains some fixtures for the AutoMapper integration tests.

## The mapping

The AutoMapper integration tests  will search a `map.php` file in a folder each
folders under this directory (see
`AutoMapper\Tests\AutoMapperTest::testAutoMapperFixtures`).

The `map.php` should return a data structure mapped. For example;

```php
<?php
// tests/AutoMapperTest/BuiltinClass/map.php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\BuiltinClass;

use AutoMapper\Tests\AutoMapperBuilder;

class BuiltinClass
{
    public function __construct(
        public \DateInterval $dateInterval
    ) {
    }
}

return AutoMapperBuilder::buildAutoMapper()->map(new BuiltinClass(new \DateInterval('P1Y')), 'array');
```

The map file could also return an iterator to provide multiple test cases.

```php
<?php

// ....

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $user = ['name' => 'foo', 'int' => ['foo' => 1]];
    yield 'ok' => $autoMapper->map($user, UserConstructorDTOWithRelation::class);

    $user = ['name' => 'foo', 'int' => ['foo' => 2]];
    yield 'ko' => $autoMapper->map($user, UserConstructorDTOWithRelation::class);
})();
```

> [!IMPORTANT]
> Each folder should be independent and should not rely on the other folders.

## The Assertion

The output of the mapping is compared to an expected result. The expected result
is located in a `expected.data` file.

If the mapping yield many results, the expectation should be in a
`expected.[provider name].data` file.

To simplify the test, the output are dumped with the Symfony VarDumper component.

## The `UPDATE_FIXTURES` environment variable

If the `UPDATE_FIXTURES` environment variable is set to `1`, the test will update
the `expected.data` files with the new output.
