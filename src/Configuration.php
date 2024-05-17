<?php

declare(strict_types=1);

namespace AutoMapper;

use MongoDB\BSON\Document;
use MongoDB\Model\BSONDocument;

final readonly class Configuration
{
    /**
     * @var list<class-string<\ArrayAccess<string, mixed>>>
     */
    public array $arrayAccessClasses;

    /**
     * @param list<class-string<\ArrayAccess<string, mixed>>> $arrayAccessClasses classes with unknown properties, implemeting ArrayAccess
     */
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
        array $arrayAccessClasses = [],
    ) {
        $arrayAccessClasses[] = \ArrayObject::class;

        // Classes provided by the mongodb extension and mongodb/mongodb package
        if (class_exists(Document::class, false)) {
            $arrayAccessClasses[] = Document::class;
            $arrayAccessClasses[] = BSONDocument::class;
        }

        $this->arrayAccessClasses = $arrayAccessClasses;
    }
}
