<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\SymfonyUidSubclass;

use AutoMapper\Tests\AutoMapperBuilder;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

class Source
{
    public function __construct(
        public UuidV4 $id,
    ) {
    }
}

class Target
{
    public UuidV4 $id;
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    // object -> object: the concrete UuidV4 target type must be preserved
    $source = new Source(Uuid::fromString('9dbee72c-ebe5-450e-843c-bb06ea7fd4be'));

    $target = $autoMapper->map($source, Target::class);

    return [
        'class' => $target->id::class,
        'value' => (string) $target->id,
    ];
})();
