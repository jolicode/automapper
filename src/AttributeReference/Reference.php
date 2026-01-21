<?php

declare(strict_types=1);

namespace AutoMapper\AttributeReference;

class Reference
{
    public function __construct(
        public string $attributeClassName,
        public int $attributeIndex,
        public string $className,
        public ?string $propertyName = null,
        public ?string $methodName = null,
    ) {
    }
}
