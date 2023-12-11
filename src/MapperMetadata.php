<?php

declare(strict_types=1);

namespace AutoMapper;

use AutoMapper\Extractor\MappingExtractorInterface;
use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Extractor\ReadAccessor;
use AutoMapper\Transformer\CallbackTransformer;
use AutoMapper\Transformer\DependentTransformerInterface;

/**
 * Mapper metadata.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
class MapperMetadata implements MapperGeneratorMetadataInterface
{
    private bool $isConstructorAllowed;
    private string $dateTimeFormat;
    private bool $attributeChecking;

    private ?\ReflectionClass $targetReflectionClass = null;

    /** @var PropertyMapping[] */
    private ?array $propertiesMapping = null;
    private ?string $className = null;
    /** @var array<string, callable> */
    private array $customMapping = [];

    public function __construct(
        private readonly MapperGeneratorMetadataRegistryInterface $metadataRegistry,
        private readonly MappingExtractorInterface $mappingExtractor,
        private readonly string $source,
        private readonly string $target,
        private readonly bool $isTargetReadOnlyClass,
        private readonly bool $mapPrivateProperties,
        private readonly string $classPrefix = 'Mapper_',
    ) {
        $this->isConstructorAllowed = true;
        $this->dateTimeFormat = \DateTime::RFC3339;
        $this->attributeChecking = true;
    }

    private function getCachedTargetReflectionClass(): \ReflectionClass
    {
        if (null === $this->targetReflectionClass) {
            $this->targetReflectionClass = new \ReflectionClass($this->getTarget());
        }

        return $this->targetReflectionClass;
    }

    public function getPropertiesMapping(): array
    {
        if (null === $this->propertiesMapping) {
            $this->buildPropertyMapping();
        }

        return $this->propertiesMapping;
    }

    public function getPropertyMapping(string $property): ?PropertyMapping
    {
        return $this->getPropertiesMapping()[$property] ?? null;
    }

    public function hasConstructor(): bool
    {
        if (!$this->isConstructorAllowed()) {
            return false;
        }

        if (\in_array($this->target, ['array', \stdClass::class], true)) {
            return false;
        }

        $reflection = $this->getCachedTargetReflectionClass();
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            return false;
        }

        $parameters = $constructor->getParameters();
        $mandatoryParameters = [];

        foreach ($parameters as $parameter) {
            if (!$parameter->isOptional() && !$parameter->allowsNull()) {
                $mandatoryParameters[] = $parameter;
            }
        }

        if (!$mandatoryParameters) {
            return true;
        }

        foreach ($mandatoryParameters as $mandatoryParameter) {
            $readAccessor = $this->mappingExtractor->getReadAccessor($this->source, $this->target, $mandatoryParameter->getName());

            if (null === $readAccessor) {
                return false;
            }
        }

        return true;
    }

    public function isTargetCloneable(): bool
    {
        try {
            $reflection = $this->getCachedTargetReflectionClass();

            return $reflection->isCloneable() && !$reflection->hasMethod('__clone');
        } catch (\ReflectionException $e) {
            // if we have a \ReflectionException, then we can't clone target
            return false;
        }
    }

    public function canHaveCircularReference(): bool
    {
        $checked = [];

        return $this->checkCircularMapperConfiguration($this, $checked);
    }

    public function getMapperClassName(): string
    {
        if (null !== $this->className) {
            return $this->className;
        }

        return $this->className = sprintf('%s%s_%s', $this->classPrefix, str_replace('\\', '_', $this->source), str_replace('\\', '_', $this->target));
    }

    public function getHash(): string
    {
        $hash = '';

        if (!\in_array($this->source, ['array', \stdClass::class], true) && class_exists($this->source)) {
            $reflection = new \ReflectionClass($this->source);
            $hash .= filemtime($reflection->getFileName());
        }

        if (!\in_array($this->target, ['array', \stdClass::class], true)) {
            $reflection = $this->getCachedTargetReflectionClass();
            $hash .= filemtime($reflection->getFileName());
        }

        $hash .= AutoMapper::VERSION_ID;

        return $hash;
    }

    public function isConstructorAllowed(): bool
    {
        return $this->isConstructorAllowed;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getDateTimeFormat(): string
    {
        return $this->dateTimeFormat;
    }

    public function getCallbacks(): array
    {
        return $this->customMapping;
    }

    public function shouldCheckAttributes(): bool
    {
        return $this->attributeChecking;
    }

    /**
     * Set DateTime format to use when generating a mapper.
     */
    public function setDateTimeFormat(string $dateTimeFormat): void
    {
        $this->dateTimeFormat = $dateTimeFormat;
    }

    /**
     * Whether the constructor should be used.
     */
    public function setConstructorAllowed(bool $isConstructorAllowed): void
    {
        $this->isConstructorAllowed = $isConstructorAllowed;
    }

    /**
     * Set a callable to use when mapping a specific property.
     */
    public function forMember(string $property, callable $callback): void
    {
        $this->customMapping[$property] = $callback;
    }

    /**
     * Whether attribute checking code should be generated.
     */
    public function setAttributeChecking(bool $attributeChecking): void
    {
        $this->attributeChecking = $attributeChecking;
    }

    private function buildPropertyMapping(): void
    {
        $this->propertiesMapping = [];

        foreach ($this->mappingExtractor->getPropertiesMapping($this) as $propertyMapping) {
            $this->propertiesMapping[$propertyMapping->property] = $propertyMapping;
        }

        foreach ($this->customMapping as $property => $callback) {
            $this->propertiesMapping[$property] = new PropertyMapping(
                new ReadAccessor(ReadAccessor::TYPE_SOURCE, $property),
                $this->mappingExtractor->getWriteMutator($this->source, $this->target, $property),
                null,
                new CallbackTransformer($property),
                $property,
                false,
                isPublic: true,
            );
        }
    }

    private function checkCircularMapperConfiguration(MapperGeneratorMetadataInterface $configuration, &$checked): bool
    {
        foreach ($configuration->getPropertiesMapping() as $propertyMapping) {
            if (!$propertyMapping->transformer instanceof DependentTransformerInterface) {
                continue;
            }

            foreach ($propertyMapping->transformer->getDependencies() as $dependency) {
                if (isset($checked[$dependency->name])) {
                    continue;
                }

                $checked[$dependency->name] = true;

                if ($dependency->source === $this->getSource() && $dependency->target === $this->getTarget()) {
                    return true;
                }

                $subConfiguration = $this->metadataRegistry->getMetadata($dependency->source, $dependency->target);

                if (null !== $subConfiguration && true === $this->checkCircularMapperConfiguration($subConfiguration, $checked)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isTargetReadOnlyClass(): bool
    {
        return $this->isTargetReadOnlyClass;
    }

    public function shouldMapPrivateProperties(): bool
    {
        return $this->mapPrivateProperties;
    }
}
