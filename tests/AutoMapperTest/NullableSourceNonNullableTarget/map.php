<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\NullableSourceNonNullableTarget;

use AutoMapper\Tests\AutoMapperBuilder;

class Source
{
    public ?\DateTimeImmutable $date = null;
    public ?string $name = null;
}

class Target
{
    public string $date = 'default date';
    public string $name = 'default name';
}

class UntypedTarget
{
    /** @var string */
    public $date = 'default date';
    /** @var string */
    public $name = 'default name';
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    // null source values on a non-nullable typed target: the transformation must not run on the null
    // value (no "format() on null" crash), a clean TypeError is thrown by the property write instead
    try {
        yield 'null-values' => $autoMapper->map(new Source(), Target::class);
    } catch (\Throwable $th) {
        yield 'null-values' => $th;
    }

    // null source values on an untyped (docblock only) target: null is assigned as-is
    yield 'null-values-untyped' => $autoMapper->map(new Source(), UntypedTarget::class);

    $source = new Source();
    $source->date = new \DateTimeImmutable('2021-01-01T00:00:00+00:00');
    $source->name = 'foo';

    yield 'with-values' => $autoMapper->map($source, Target::class);
})();
