<?php

declare(strict_types=1);

namespace AutoMapper\AttributeReference;

final class ReflectionReference
{
    /** @var array<string, ReflectionReference> */
    private static array $class = [];
    /** @var array<string, ReflectionReference> */
    private static array $method = [];
    /** @var array<string, ReflectionReference> */
    private static array $property = [];

    /**
     * @param \ReflectionClass<object>|\ReflectionMethod|\ReflectionProperty $reflection
     */
    public function __construct(
        public readonly string $hash,
        public readonly \ReflectionClass|\ReflectionMethod|\ReflectionProperty $reflection,
    ) {
    }

    /**
     * @param class-string $className
     */
    public static function fromClass(string $className): self
    {
        if (!isset(self::$class[$className])) {
            $reflection = new \ReflectionClass($className);
            self::$class[$className] = new self($className, $reflection);
        }

        return self::$class[$className];
    }

    public static function fromMethod(string $className, string $methodName): self
    {
        $key = $className . '::' . $methodName;
        if (!isset(self::$method[$key])) {
            $reflection = new \ReflectionMethod($className, $methodName);
            self::$method[$key] = new self($key, $reflection);
        }

        return self::$method[$key];
    }

    public static function fromProperty(string $className, string $propertyName): self
    {
        $key = $className . '::$' . $propertyName;
        if (!isset(self::$property[$key])) {
            $reflection = new \ReflectionProperty($className, $propertyName);
            self::$property[$key] = new self($key, $reflection);
        }

        return self::$property[$key];
    }
}
