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
     * @param class-string|'array'|null                                 $source      The specific source class name or array. If null this attribute will be used for all source classes.
     * @param string|null                                               $name        The source property name. If null, the target property name will be used.
     * @param int|null                                                  $maxDepth    The maximum depth of the mapping. If null, the default max depth will be used.
     * @param string|callable(mixed $value, object $object): mixed|null $transformer A transformer id or a callable that transform the value during mapping
     * @param bool|null                                                 $ignore      if true, the property will be ignored during mapping
     * @param string|null                                               $if          The condition to map the property, using the expression language
     */
    public function __construct(
        public ?string $source = null,
        public ?string $name = null,
        public ?int $maxDepth = null,
        public mixed $transformer = null,
        public ?bool $ignore = null,
        public ?string $if = null,
    ) {
    }
}
