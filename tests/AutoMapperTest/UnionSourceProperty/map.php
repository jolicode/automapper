<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\UnionSourceProperty;

use AutoMapper\Tests\AutoMapperBuilder;

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

class Source
{
    public \DateTime|string $date;
    public Status|string $status;
}

class Target
{
    public \DateTimeImmutable $date;
    public string $status;
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    // union branches without a native type check (string -> \DateTimeImmutable, enum -> string)
    $source = new Source();
    $source->date = '2021-01-01T00:00:00+00:00';
    $source->status = Status::Active;

    yield 'string-date-enum-status' => $autoMapper->map($source, Target::class);

    $source = new Source();
    $source->date = new \DateTime('2022-02-02T00:00:00+00:00');
    $source->status = 'inactive';

    yield 'datetime-date-string-status' => $autoMapper->map($source, Target::class);
})();
