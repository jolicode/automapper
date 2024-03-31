<?php

declare(strict_types=1);

namespace AutoMapper;

final readonly class Configuration
{
    public const CONSTRUCTOR_STRATEGY_AUTO = 'auto';
    public const CONSTRUCTOR_STRATEGY_ALWAYS = 'always';
    public const CONSTRUCTOR_STRATEGY_NEVER = 'never';

    public function __construct(
        /**
         * Class prefix used to prefix the class name of the generated mappers.
         */
        public string $classPrefix = 'Mapper_',
        /**
         * If the constructor should be used to map the properties.
         */
        public string $constructorStrategy = self::CONSTRUCTOR_STRATEGY_AUTO,
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
    ) {
    }
}
