<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourcePropertyType;

use AutoMapper\Attribute\MapFrom;
use AutoMapper\Tests\AutoMapperBuilder;

class Dto
{
    /** @var array<float> */
    #[MapFrom(source: 'array', sourcePropertyType: 'array<string>')]
    public array $age;
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield $autoMapper->map(['age' => ['10']], Dto::class);
})();
