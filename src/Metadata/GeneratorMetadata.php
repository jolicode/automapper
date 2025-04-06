<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\ConstructorStrategy;
use AutoMapper\Generator\VariableRegistry;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

/**
 * @internal
 */
final class GeneratorMetadata
{
    public readonly VariableRegistry $variableRegistry;

    /** @var array<string, Dependency> */
    private array $dependencies = [];

    public function __construct(
        public readonly MapperMetadata $mapperMetadata,
        /** @var PropertyMetadata[] */
        public readonly array $propertiesMetadata,
        public readonly bool $checkAttributes = true,
        public readonly ConstructorStrategy $constructorStrategy = ConstructorStrategy::AUTO,
        public readonly bool $allowReadOnlyTargetToPopulate = false,
        public readonly bool $strictTypes = false,
        public readonly ?string $provider = null,
    ) {
        $this->variableRegistry = new VariableRegistry();
    }

    /**
     * @return array<string, Dependency>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function addDependency(Dependency $dependency): void
    {
        $this->dependencies[$dependency->mapperDependency->name] = $dependency;
    }

    public function canHaveCircularReference(): bool
    {
        $checked = [];

        return 'array' !== $this->mapperMetadata->source && $this->checkCircularMapperConfiguration($this, $checked);
    }

    public function isTargetUserDefined(): bool
    {
        if (null === $this->mapperMetadata->targetReflectionClass) {
            return false;
        }

        return $this->mapperMetadata->targetReflectionClass->isUserDefined();
    }

    public function isSourceUserDefined(): bool
    {
        if (null === $this->mapperMetadata->sourceReflectionClass) {
            return false;
        }

        return $this->mapperMetadata->sourceReflectionClass->isUserDefined();
    }

    public function hasConstructor(): bool
    {
        if ($this->constructorStrategy === ConstructorStrategy::NEVER) {
            return false;
        }

        if ($this->constructorStrategy === ConstructorStrategy::ALWAYS) {
            return true;
        }

        if (\in_array($this->mapperMetadata->target, ['array', \stdClass::class], true)) {
            return false;
        }

        $constructor = $this->mapperMetadata->targetReflectionClass?->getConstructor();

        if (null === $constructor) {
            return false;
        }

        if ($constructor->getDeclaringClass()->isInterface() || $constructor->getDeclaringClass()->isAbstract()) {
            return false;
        }

        $parameters = $constructor->getParameters();
        $mandatoryParameters = [];

        foreach ($parameters as $parameter) {
            if (!$parameter->isOptional()) {
                $mandatoryParameters[] = $parameter;
            }
        }

        if (!$mandatoryParameters) {
            return true;
        }

        foreach ($mandatoryParameters as $mandatoryParameter) {
            // Find property mapping for mandatory parameter
            $propertyMapping = null;

            foreach ($this->propertiesMetadata as $mapping) {
                if ($mapping->target->property === $mandatoryParameter->getName()) {
                    $propertyMapping = $mapping;
                    break;
                }
            }

            if (null === $propertyMapping) {
                return false;
            }

            if (null === $propertyMapping->source->accessor && !($propertyMapping->transformer instanceof PropertyTransformerInterface)) {
                return false;
            }
        }

        return true;
    }

    public function isTargetReadonlyClass(): bool
    {
        if (null === $this->mapperMetadata->targetReflectionClass) {
            return false;
        }

        return $this->mapperMetadata->targetReflectionClass->isReadOnly();
    }

    public function isTargetCloneable(): bool
    {
        $reflection = $this->mapperMetadata->targetReflectionClass;

        if (null === $reflection) {
            return false;
        }

        return $reflection->isCloneable() && !$reflection->hasMethod('__clone');
    }

    /**
     * @return array<string>
     */
    public function getPropertiesInConstructor(): array
    {
        if (!$this->hasConstructor()) {
            return [];
        }

        $constructor = $this->mapperMetadata->targetReflectionClass?->getConstructor();

        if (null === $constructor) {
            return [];
        }

        $properties = [];

        foreach ($this->propertiesMetadata as $propertyMetadata) {
            if (null === $propertyMetadata->target->parameterInConstructor) {
                continue;
            }

            $properties[] = $propertyMetadata->target->property;
        }

        return $properties;
    }

    public function getTargetProperty(string $property): ?PropertyMetadata
    {
        foreach ($this->propertiesMetadata as $propertyMetadata) {
            if ($propertyMetadata->target->property === $property) {
                return $propertyMetadata;
            }
        }

        return null;
    }

    public function getTargetPropertyWithConstructor(string $parameterName): ?PropertyMetadata
    {
        foreach ($this->propertiesMetadata as $propertyMetadata) {
            if ($propertyMetadata->target->parameterInConstructor === $parameterName) {
                return $propertyMetadata;
            }
        }

        return null;
    }

    /**
     * @param array<string, true> $checked
     */
    private function checkCircularMapperConfiguration(self $metadata, &$checked): bool
    {
        foreach ($metadata->getDependencies() as $name => $dependency) {
            if (isset($checked[$name])) {
                continue;
            }

            $checked[$name] = true;

            if ($dependency->mapperDependency->source === $this->mapperMetadata->source && $dependency->mapperDependency->target === $this->mapperMetadata->target) {
                return true;
            }

            if (true === $this->checkCircularMapperConfiguration($dependency->metadata, $checked)) {
                return true;
            }
        }

        return false;
    }
}
