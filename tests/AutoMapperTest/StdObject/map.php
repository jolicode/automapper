<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\StdObject;

use AutoMapper\Tests\AutoMapperBuilder;

$user = new \stdClass();
$user->id = 1;
$nestedStd = new \stdClass();
$nestedStd->id = 2;
$user->nestedStd = $nestedStd;

return AutoMapperBuilder::buildAutoMapper()->map($user, \stdClass::class);
