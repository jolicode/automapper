<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Configuration;
use AutoMapper\Event\PropertyMetadataEvent;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * @internal
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
abstract class MappingExtractor implements MappingExtractorInterface
{
    public function __construct(
        protected readonly Configuration $configuration,
        protected readonly PropertyListExtractorInterface $propertyInfoExtractor,
        protected readonly PropertyReadInfoExtractorInterface $readInfoExtractor,
        protected readonly PropertyWriteInfoExtractorInterface $writeInfoExtractor,
        protected readonly PropertyTypeExtractorInterface $sourceTypeExtractor,
        protected readonly PropertyTypeExtractorInterface $targetTypeExtractor,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getProperties(string $class, bool $withConstructorParameters = false): iterable
    {
        if ($class === 'array' || $class === \stdClass::class) {
            return [];
        }

        $properties = $this->propertyInfoExtractor->getProperties($class) ?? [];

        if ($withConstructorParameters) {
            $properties = array_values(
                array_unique(
                    [...$properties, ...$this->getConstructorParameters($class)]
                )
            );
        }

        return $properties;
    }

    /**
     * @param class-string|'array' $class
     *
     * @return list<string>
     */
    private function getConstructorParameters(string $class): iterable
    {
        if ($class === 'array' || $class === \stdClass::class) {
            return [];
        }

        try {
            return array_map(
                static fn (\ReflectionParameter $parameter) => $parameter->getName(),
                (new \ReflectionClass($class))->getMethod('__construct')->getParameters()
            );
        } catch (\ReflectionException) {
            return [];
        }
    }

    public function getReadAccessor(string $class, string $property, bool $allowExtraProperties = false): ?ReadAccessorInterface
    {
        // split to first dot for nested properties
        $exploded = explode('.', $property, 2);

        if (2 === \count($exploded)) {
            [$parent, $child] = $exploded;
        } else {
            return $this->doGetReadAccessor($class, $property, $allowExtraProperties);
        }

        if ('array' === $class) {
            $type = Type::array();
        } elseif (\stdClass::class === $class) {
            $type = Type::object(\stdClass::class);
        } else {
            $type = $this->sourceTypeExtractor->getType($class, $parent);
        }

        $parentAccessor = $this->doGetReadAccessor($class, $parent, $allowExtraProperties);

        if ($type instanceof Type\ObjectType) {
            /** @var class-string $childClass */
            $childClass = $type->getClassName();
            $childAccessor = $this->getReadAccessor($childClass, $child, $allowExtraProperties);
        } elseif ($type?->isIdentifiedBy(TypeIdentifier::ARRAY)) {
            $childAccessor = $this->getReadAccessor('array', $child, $allowExtraProperties);
        } else {
            return null;
        }

        if (null === $parentAccessor || null === $childAccessor) {
            return null;
        }

        return new NestedReadAccessor($parentAccessor, $childAccessor);
    }

    public function getWriteMutator(string $source, string $target, string $property, array $context = [], bool $allowExtraProperties = false): ?WriteMutatorInterface
    {
        // split to last dot for nested properties
        $lastDotPosition = strrpos($property, '.');

        if (false === $lastDotPosition) {
            return $this->doGetWriteMutator($target, $property, $context, $allowExtraProperties);
        }

        $parent = substr($property, 0, $lastDotPosition);
        $child = substr($property, $lastDotPosition + 1);
        $accessor = $this->getReadAccessor($target, $parent, $allowExtraProperties);

        if ($target === 'array') {
            $lastAccessorType = Type::array();
        } elseif (\stdClass::class === $target) {
            $lastAccessorType = Type::object(\stdClass::class);
        } else {
            $lastAccessorType = $this->findLastTypeForAccessor($target, $parent);
        }

        if (null === $lastAccessorType) {
            return null;
        }

        if ($lastAccessorType instanceof Type\ObjectType) {
            /** @var class-string|'array' $targetClass */
            $targetClass = $lastAccessorType->getClassName();
        } elseif ($lastAccessorType->isIdentifiedBy(TypeIdentifier::ARRAY)) {
            $targetClass = 'array';
        } else {
            return null;
        }

        $mutator = $this->doGetWriteMutator($targetClass, $child, $context, $allowExtraProperties);

        if (null === $accessor || null === $mutator) {
            return null;
        }

        return new NestedWriteMutator($accessor, $mutator);
    }

    public function getCheckExists(string $class, string $property): bool
    {
        if ('array' === $class || \stdClass::class === $class) {
            return true;
        }

        return false;
    }

    public function getGroups(string $class, string $property): ?array
    {
        return null;
    }

    public function getDateTimeFormat(PropertyMetadataEvent $propertyMetadataEvent): string
    {
        if (null !== $propertyMetadataEvent->dateTimeFormat) {
            return $propertyMetadataEvent->dateTimeFormat;
        }

        if (null !== $propertyMetadataEvent->mapperMetadata->dateTimeFormat) {
            return $propertyMetadataEvent->mapperMetadata->dateTimeFormat;
        }

        return $this->configuration->dateTimeFormat;
    }

    /**
     * @param class-string $source
     */
    private function findLastTypeForAccessor(string $source, string $property): ?Type
    {
        $exploded = explode('.', $property, 2);

        if (2 === \count($exploded)) {
            [$parent, $child] = $exploded;
        } else {
            return $this->sourceTypeExtractor->getType($source, $property);
        }

        $parentType = $this->sourceTypeExtractor->getType($source, $parent);

        if ($parentType instanceof Type\ObjectType) {
            /** @var class-string $className */
            $className = $parentType->getClassName();

            return $this->findLastTypeForAccessor($className, $child);
        }

        return $parentType;
    }

    /**
     * @param class-string|'array' $class
     */
    private function doGetReadAccessor(string $class, string $property, bool $allowExtraProperties = false): ?ReadAccessorInterface
    {
        if ('array' === $class) {
            return new ArrayReadAccessor($property);
        }

        if (\stdClass::class === $class) {
            return new PropertyReadAccessor($property);
        }

        $readInfo = $this->readInfoExtractor->getReadInfo($class, $property);

        if (null === $readInfo) {
            if ($allowExtraProperties) {
                $implements = class_implements($class);

                if ($implements !== false && \in_array(\ArrayAccess::class, $implements, true)) {
                    return new ArrayReadAccessor($property, true);
                }
            }

            return null;
        }

        if (PropertyReadInfo::TYPE_METHOD === $readInfo->getType()) {
            return new MethodReadAccessor($property, $readInfo->getName(), $class, PropertyReadInfo::VISIBILITY_PUBLIC !== $readInfo->getVisibility());
        }

        return new PropertyReadAccessor(
            $readInfo->getName(),
            PropertyReadInfo::VISIBILITY_PUBLIC !== $readInfo->getVisibility(),
        );
    }

    /**
     * @param class-string|'array' $target
     * @param array<string, mixed> $context
     */
    private function doGetWriteMutator(string $target, string $property, array $context = [], bool $allowExtraProperties = false): ?WriteMutatorInterface
    {
        $writeInfo = $this->writeInfoExtractor->getWriteInfo($target, $property, $context);

        if (null === $writeInfo || PropertyWriteInfo::TYPE_NONE === $writeInfo->getType()) {
            if ('array' === $target) {
                return new ArrayWriteMutator($property);
            }

            if (\stdClass::class === $target) {
                return new PropertyWriteMutator($property, false);
            }

            if ($allowExtraProperties) {
                $implements = class_implements($target);

                if ($implements !== false && \in_array(\ArrayAccess::class, $implements, true)) {
                    return new ArrayWriteMutator($property);
                }
            }

            return null;
        }

        if (PropertyWriteInfo::TYPE_CONSTRUCTOR === $writeInfo->getType()) {
            $parameter = new \ReflectionParameter([$target, '__construct'], $writeInfo->getName());

            return new ConstructorWriteMutator($parameter);
        }

        if (PropertyWriteInfo::TYPE_METHOD === $writeInfo->getType()) {
            return new MethodWriteMutator($writeInfo->getName());
        }

        if (PropertyWriteInfo::TYPE_ADDER_AND_REMOVER === $writeInfo->getType()) {
            return new AddRemoveWriteMutator($writeInfo->getAdderInfo()->getName(), $writeInfo->getRemoverInfo()->getName());
        }

        return new PropertyWriteMutator($writeInfo->getName(), PropertyReadInfo::VISIBILITY_PUBLIC !== $writeInfo->getVisibility());
    }
}
