<?php

declare(strict_types=1);

namespace AutoMapper;

use AutoMapper\Loader\FileReloadStrategy;

final readonly class Configuration
{
    public function __construct(
        /**
         * Class prefix used to prefix the class name of the generated mappers.
         */
        public string $classPrefix = 'Mapper_',
        /**
         * If the constructor should be used to map the properties.
         */
        public ConstructorStrategy $constructorStrategy = ConstructorStrategy::AUTO,
        /**
         * The date time format used to map \DateTimeInterface properties.
         */
        public string $dateTimeFormat = \DateTime::RFC3339,
        /**
         * If the attributes should be checked to map the properties.
         */
        public bool $attributeChecking = true,
        /**
         * If the mappers should be automatically generated if it does not exist
         * Otherwise the mapper will throw a MapperNotFoundException.
         */
        public bool $autoRegister = true,
        /**
         * Whether the private properties should be mapped.
         */
        public bool $mapPrivateProperties = true,
        /**
         * Does the mapper should throw an exception if the target is read-only.
         */
        public bool $allowReadOnlyTargetToPopulate = false,
        /**
         * Add declare(strict_types=1) to generated code.
         */
        public bool $strictTypes = false,
        /**
         * The strategy to use to generate the mappers between each request.
         */
        public FileReloadStrategy $reloadStrategy = FileReloadStrategy::ON_CHANGE,
        /**
         * Allow extra properties to be mapped when the target or the source implements ArrayAccess class.
         */
        public bool $allowExtraProperties = false,
    ) {
    }
}
