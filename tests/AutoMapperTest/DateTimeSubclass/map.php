<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\DateTimeSubclass;

use AutoMapper\Tests\AutoMapperBuilder;

class CustomImmutable extends \DateTimeImmutable
{
}

class CustomMutable extends \DateTime
{
}

class Source
{
    public \DateTimeImmutable $date;
}

class Target
{
    public CustomImmutable $date;
}

class MutableTarget
{
    public CustomMutable $date;
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $source = new Source();
    $source->date = new \DateTimeImmutable('2021-01-01T00:00:00+00:00');

    // datetime -> custom immutable subclass
    $immutable = $autoMapper->map($source, Target::class);

    // datetime -> custom mutable subclass
    $mutable = $autoMapper->map($source, MutableTarget::class);

    return [
        'immutable_class' => $immutable->date::class,
        'immutable_value' => $immutable->date->format(\DateTimeInterface::RFC3339),
        'mutable_class' => $mutable->date::class,
        'mutable_value' => $mutable->date->format(\DateTimeInterface::RFC3339),
    ];
})();
