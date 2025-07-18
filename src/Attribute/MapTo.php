<?php

declare(strict_types=1);

namespace AutoMapper\Attribute;

/**
 * Configures a property to map to.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
final readonly class MapTo
{
    /**
     * @param class-string<object>|'array'|array<class-string<object>|'array'>|null                                    $target                 The specific target class name or array. If null this attribute will be used for all target classes.
     * @param string|null                                                                                              $property               The target property name. If null, the source property name will be used.
     * @param int|null                                                                                                 $maxDepth               The maximum depth of the mapping. If null, the default max depth will be used.
     * @param string|callable(mixed $value, object|array<string, mixed> $source, array<string, mixed> $context): mixed $transformer            A transformer id or a callable that transform the value during mapping
     * @param bool|null                                                                                                $ignore                 If true, the property will be ignored during mapping
     * @param string|null                                                                                              $if                     The condition to map the property, using the expression language
     * @param string[]|null                                                                                            $groups                 The groups to map the property
     * @param string|null                                                                                              $dateTimeFormat         The date-time format to use when transforming this property
     * @param bool|null                                                                                                $extractTypesFromGetter If true, the types will be extracted from the getter method
     * @param bool|null                                                                                                $identifier             If true, the property will be used as an identifier
     */
    public function __construct(
        public string|array|null $target = null,
        public ?string $property = null,
        public ?int $maxDepth = null,
        public mixed $transformer = null,
        public ?bool $ignore = null,
        public ?string $if = null,
        public ?array $groups = null,
        public int $priority = 0,
        public ?string $dateTimeFormat = null,
        public ?bool $extractTypesFromGetter = null,
        public ?bool $identifier = null,
    ) {
    }
}
