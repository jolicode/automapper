<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\BuiltinClass;

use AutoMapper\Tests\AutoMapperBuilder;

class BuiltinClass
{
    public function __construct(
        public \DateInterval $dateInterval,
    ) {
    }
}

$autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

return $autoMapper->map(new BuiltinClass(new \DateInterval('P1Y')), 'array');
