<?php

declare(strict_types=1);

namespace AutoMapper\Attribute;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\MapperMetadata\MapperType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class MapTo implements PropertyAttribute
{
    /**
     * @param non-empty-string          $propertyName
     * @param 'array'|class-string|null $target
     */
    public function __construct(
        public string $propertyName,
        public string|null $target = null,
    ) {
    }

    public function supports(PropertyMapping $propertyMapping): bool
    {
        if ($propertyMapping->mapperMetadata->mapperType() === MapperType::FROM_TARGET) {
            return false;
        }

        return null === $this->target || $propertyMapping->mapperMetadata->getTarget() === $this->target;
    }
}
