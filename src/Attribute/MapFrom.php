<?php

declare(strict_types=1);

namespace AutoMapper\Attribute;

/**
 * Configures a property to map from.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
final readonly class MapFrom
{
    /**
     * @param class-string<object>|'array'|array<class-string<object>|'array'>|null $source         The specific source class name or array. If null this attribute will be used for all source classes.
     * @param string|null                                                           $property       The source property name. If null, the target property name will be used.
     * @param int|null                                                              $maxDepth       The maximum depth of the mapping. If null, the default max depth will be used.
     * @param string|callable(mixed $value, object $object): mixed|null             $transformer    A transformer id or a callable that transform the value during mapping
     * @param bool|null                                                             $ignore         If true, the property will be ignored during mapping
     * @param string|null                                                           $if             The condition to map the property, using the expression language
     * @param string[]|null                                                         $groups         The groups to map the property
     * @param string|null                                                           $dateTimeFormat The date-time format to use when transforming this property
     */
    public function __construct(
        public string|array|null $source = null,
        public ?string $property = null,
        public ?int $maxDepth = null,
        public mixed $transformer = null,
        public ?bool $ignore = null,
        public ?string $if = null,
        public ?array $groups = null,
        public int $priority = 0,
        public ?string $dateTimeFormat = null,
    ) {
    }
}
