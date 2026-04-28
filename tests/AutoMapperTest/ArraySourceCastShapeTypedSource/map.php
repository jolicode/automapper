<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastShapeTypedSource;

use AutoMapper\Tests\AutoMapperBuilder;

class SourceDto
{
    /** @var array{age: string, score: string, active: string} */
    public array $data;
}

class TargetDto
{
    /** @var array{age: int, score: float, active: bool} */
    public array $data;
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $source = new SourceDto();
    $source->data = ['age' => '30', 'score' => '9.5', 'active' => '1'];

    yield $autoMapper->map($source, TargetDto::class);
})();
