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

    public function getWriteMutator(string $source, string $target, string $property, array $context = [], bool $allowExtraProperties = false): ?WriteMutatorInterface
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
}
