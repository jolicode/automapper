<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\NoProperties;

use AutoMapper\Tests\AutoMapperBuilder;

class FooNoProperties
{
}

$noProperties = new FooNoProperties();

return AutoMapperBuilder::buildAutoMapper()->map($noProperties, FooNoProperties::class);
