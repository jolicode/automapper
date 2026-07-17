<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\GlobalNamespaceClasses;

use AutoMapper\Tests\AutoMapperBuilder;

require_once __DIR__ . '/classes.php';

$item = new \AutoMapperGlobalItemSource();
$item->name = 'foo';

$source = new \AutoMapperGlobalSource();
$source->items = [$item];

return AutoMapperBuilder::buildAutoMapper()->map($source, \AutoMapperGlobalDto::class);
