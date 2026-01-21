<?php

declare(strict_types=1);

namespace AutoMapper\AttributeReference;

/** @template T */
final class AttributeInstance
{
    /** @var array<string, array<string, array<int, T|null>>> */
    private static array $instances = [];

    /**
     * @param class-string<T> $attributeClass
     *
     * @return ?T
     */
    public static function get(string $attributeClass, ReflectionReference $reference, int $index = 0)
    {
        if (!\array_key_exists($index, self::$instances[$reference->hash][$attributeClass] ?? [])) {
            $attribute = $reference->reflection->getAttributes($attributeClass)[$index] ?? null;
            self::$instances[$reference->hash][$attributeClass][$index] = $attribute?->newInstance();
        }

        /** @return ?T */
        return self::$instances[$reference->hash][$attributeClass][$index];
    }
}
