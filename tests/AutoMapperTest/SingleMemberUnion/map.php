<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\SingleMemberUnion;

use AutoMapper\Tests\AutoMapperBuilder;

class Money
{
    public int $amount = 10;
}

class Source
{
    // only the int branch has a transformer to string, the Money branch has none
    public int|Money $value;
}

class Target
{
    public ?string $value = null;
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    // int value: transformed to string as before
    $intSource = new Source();
    $intSource->value = 42;

    // object value of the other union member: must not be blindly cast to string
    $objectSource = new Source();
    $objectSource->value = new Money();

    try {
        $objectResult = ['value' => $autoMapper->map($objectSource, Target::class)->value];
    } catch (\Throwable $th) {
        $objectResult = ['error' => $th::class];
    }

    return [
        'int' => $autoMapper->map($intSource, Target::class)->value,
        'object' => $objectResult,
    ];
})();
