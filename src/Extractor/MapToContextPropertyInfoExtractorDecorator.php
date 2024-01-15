<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Attribute\MapToContext;
use Symfony\Component\PropertyInfo\Extractor\ConstructorArgumentTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

/**
 * @internal
 */
final readonly class MapToContextPropertyInfoExtractorDecorator implements PropertyListExtractorInterface, PropertyTypeExtractorInterface, PropertyAccessExtractorInterface, PropertyInitializableExtractorInterface, PropertyReadInfoExtractorInterface, PropertyWriteInfoExtractorInterface, ConstructorArgumentTypeExtractorInterface
{
    public function __construct(
        private ReflectionExtractor $decorated
    ) {
    }

    public function getReadInfo(string $class, string $property, array $context = []): ?PropertyReadInfo
    {
        $readInfo = $this->decorated->getReadInfo($class, $property, $context);

        if ($class === 'array') {
            return $readInfo;
        }

        if (null === $readInfo || $readInfo->getType() === PropertyReadInfo::TYPE_PROPERTY && PropertyReadInfo::VISIBILITY_PUBLIC !== $readInfo->getVisibility()) {
            $reflClass = new \ReflectionClass($class);
            $camelProp = $this->camelize($property);

            // if we have not found a getter, it might be because it has parameters with MapToContext attribute
            foreach (ReflectionExtractor::$defaultAccessorPrefixes as $prefix) {
                $methodName = $prefix . $camelProp;

                if (
                    $reflClass->hasMethod($methodName)
                    && $reflClass->getMethod($methodName)->getModifiers() === \ReflectionMethod::IS_PUBLIC
                    && $reflClass->getMethod($methodName)->getNumberOfRequiredParameters()
                    && $this->allParametersHaveMapToContextAttribute($reflClass->getMethod($methodName))
                ) {
                    $method = $reflClass->getMethod($methodName);

                    return new PropertyReadInfo(PropertyReadInfo::TYPE_METHOD, $methodName, PropertyReadInfo::VISIBILITY_PUBLIC, $method->isStatic(), false);
                }
            }
        }

        return $readInfo;
    }

    public function isReadable(string $class, string $property, array $context = []): bool
    {
        if ($this->isAllowedProperty($class, $property)) {
            return true;
        }

        return null !== $this->getReadInfo($class, $property, $context);
    }

    public function isWritable(string $class, string $property, array $context = []): bool
    {
        return $this->decorated->isWritable($class, $property, $context);
    }

    private function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    private function allParametersHaveMapToContextAttribute(\ReflectionMethod $method): bool
    {
        foreach ($method->getParameters() as $parameter) {
            if (!$parameter->getAttributes(MapToContext::class)) {
                return false;
            }
        }

        return true;
    }

    private function isAllowedProperty(string $class, string $property, bool $writeAccessRequired = false): bool
    {
        try {
            $reflectionProperty = new \ReflectionProperty($class, $property);

            if (\PHP_VERSION_ID >= 80100 && $writeAccessRequired && $reflectionProperty->isReadOnly()) {
                return false;
            }

            return (bool) ($reflectionProperty->getModifiers() & \ReflectionProperty::IS_PUBLIC);
        } catch (\ReflectionException) {
            // Return false if the property doesn't exist
        }

        return false;
    }

    public function getTypesFromConstructor(string $class, string $property): ?array
    {
        return $this->decorated->getTypesFromConstructor($class, $property);
    }

    public function isInitializable(string $class, string $property, array $context = []): ?bool
    {
        return $this->decorated->isInitializable($class, $property, $context);
    }

    public function getProperties(string $class, array $context = []): ?array
    {
        return $this->decorated->getProperties($class, $context);
    }

    public function getTypes(string $class, string $property, array $context = []): ?array
    {
        return $this->decorated->getTypes($class, $property, $context);
    }

    public function getWriteInfo(string $class, string $property, array $context = []): ?PropertyWriteInfo
    {
        return $this->decorated->getWriteInfo($class, $property, $context);
    }
}
