<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Attribute\MapIdentifier;
use AutoMapper\Configuration;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

/**
 * @internal
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
abstract class MappingExtractor implements MappingExtractorInterface
{
    public function __construct(
        protected readonly Configuration $configuration,
        protected readonly PropertyInfoExtractorInterface $propertyInfoExtractor,
        protected readonly PropertyReadInfoExtractorInterface $readInfoExtractor,
        protected readonly PropertyWriteInfoExtractorInterface $writeInfoExtractor,
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

    public function getReadAccessor(string $class, string $property, bool $allowExtraProperties = false): ?ReadAccessor
    {
        if ('array' === $class) {
            return new ReadAccessor(ReadAccessor::TYPE_ARRAY_DIMENSION, $property);
        }

        if (\stdClass::class === $class) {
            return new ReadAccessor(ReadAccessor::TYPE_PROPERTY, $property);
        }

        $readInfo = $this->readInfoExtractor->getReadInfo($class, $property);

        if (null === $readInfo) {
            if ($allowExtraProperties) {
                $implements = class_implements($class);

                if ($implements !== false && \in_array(\ArrayAccess::class, $implements, true)) {
                    return new ReadAccessor(ReadAccessor::TYPE_ARRAY_ACCESS, $property);
                }
            }

            return null;
        }

        $type = ReadAccessor::TYPE_PROPERTY;

        if (PropertyReadInfo::TYPE_METHOD === $readInfo->getType()) {
            $type = ReadAccessor::TYPE_METHOD;
        }

        return new ReadAccessor(
            $type,
            $readInfo->getName(),
            $class,
            PropertyReadInfo::VISIBILITY_PUBLIC !== $readInfo->getVisibility(),
            $property
        );
    }

    public function getWriteMutator(string $source, string $target, string $property, array $context = [], bool $allowExtraProperties = false): ?WriteMutator
    {
        $writeInfo = $this->writeInfoExtractor->getWriteInfo($target, $property, $context);
        $removeMethodName = null;

        if (null === $writeInfo || PropertyWriteInfo::TYPE_NONE === $writeInfo->getType()) {
            if ('array' === $target) {
                return new WriteMutator(WriteMutator::TYPE_ARRAY_DIMENSION, $property, false);
            }

            if (\stdClass::class === $target) {
                return new WriteMutator(WriteMutator::TYPE_PROPERTY, $property, false);
            }

            if ($allowExtraProperties) {
                $implements = class_implements($target);

                if ($implements !== false && \in_array(\ArrayAccess::class, $implements, true)) {
                    return new WriteMutator(WriteMutator::TYPE_ARRAY_DIMENSION, $property, false);
                }
            }

            return null;
        }

        if (PropertyWriteInfo::TYPE_CONSTRUCTOR === $writeInfo->getType()) {
            $parameter = new \ReflectionParameter([$target, '__construct'], $writeInfo->getName());

            return new WriteMutator(WriteMutator::TYPE_CONSTRUCTOR, $writeInfo->getName(), false, $parameter);
        }

        $type = WriteMutator::TYPE_PROPERTY;

        if (PropertyWriteInfo::TYPE_METHOD === $writeInfo->getType()) {
            $type = WriteMutator::TYPE_METHOD;
        }

        if (PropertyWriteInfo::TYPE_ADDER_AND_REMOVER === $writeInfo->getType()) {
            $type = WriteMutator::TYPE_ADDER_AND_REMOVER;
            $removeMethodName = $writeInfo->getRemoverInfo()->getName();
            $writeInfo = $writeInfo->getAdderInfo();
        }

        return new WriteMutator(
            $type,
            $writeInfo->getName(),
            PropertyReadInfo::VISIBILITY_PUBLIC !== $writeInfo->getVisibility(),
            null,
            $removeMethodName,
        );
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

    public function isIdentifier(?\ReflectionClass $targetReflectionClass, TargetPropertyMetadata $target): bool
    {
        if ($targetReflectionClass === null) {
            return false;
        }

        // check on reflection property
        if ($targetReflectionClass->hasProperty($target->property)) {
            $reflectionProperty = $targetReflectionClass->getProperty($target->property);

            if (\count($reflectionProperty->getAttributes(MapIdentifier::class)) > 0) {
                return true;
            }
        }

        // check on the getter to read, it may not be defined yet so we cannot skip this everytime
        if ($target->readAccessor !== null) {
            // check with read accessor on property
            if ($target->readAccessor->type === ReadAccessor::TYPE_PROPERTY && $targetReflectionClass->hasProperty(
                $target->readAccessor->property
            )) {
                $reflectionProperty = $targetReflectionClass->getProperty(
                    $target->readAccessor->property
                );

                if (\count($reflectionProperty->getAttributes(MapIdentifier::class)) > 0) {
                    return true;
                }
            }

            // check with read accessor on method
            if ($target->readAccessor->type === ReadAccessor::TYPE_METHOD && $targetReflectionClass->hasMethod(
                $target->readAccessor->property
            )) {
                $reflectionMethod = $targetReflectionClass->getMethod(
                    $target->readAccessor->property
                );

                if (\count($reflectionMethod->getAttributes(MapIdentifier::class)) > 0) {
                    return true;
                }
            }
        }

        // same for the write mutator, we cannot globally skip this check as it may be defined latter
        if ($target->writeMutator !== null) {
            // check for property
            if ($target->writeMutator->type === WriteMutator::TYPE_PROPERTY && $targetReflectionClass->hasProperty($target->writeMutator->property)) {
                $reflectionProperty = $targetReflectionClass->getProperty(
                    $target->writeMutator->property
                );

                if (\count($reflectionProperty->getAttributes(MapIdentifier::class)) > 0) {
                    return true;
                }
            }

            // check for parameter in constructor
            if ($target->writeMutator->type === WriteMutator::TYPE_CONSTRUCTOR && $target->writeMutator->parameter !== null) {
                if (\count($target->writeMutator->parameter->getAttributes(MapIdentifier::class)) > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
