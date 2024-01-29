<?php

declare(strict_types=1);

namespace AutoMapper\Attribute;

use AutoMapper\Extractor\PropertyMapping;

interface PropertyAttribute
{
    public function supports(PropertyMapping $propertyMapping): bool;
}
