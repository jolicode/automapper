<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Configuration;
use AutoMapper\Event\PropertyMetadataEvent;
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
    public function getProperties(string $class): iterable
    {
        if ($class === 'array' || $class === \stdClass::class) {
            return [];
        }

        return $this->propertyInfoExtractor->getProperties($class) ?? [];
    }

    public function getReadAccessor(string $class, string $property): ?ReadAccessor
    {
        if ('array' === $class) {
            return new ReadAccessor(ReadAccessor::TYPE_ARRAY_DIMENSION, $property);
        }

        if (\stdClass::class === $class) {
            return new ReadAccessor(ReadAccessor::TYPE_PROPERTY, $property);
        }

        $readInfo = $this->readInfoExtractor->getReadInfo($class, $property);

        if (null === $readInfo) {
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

    public function getWriteMutator(string $source, string $target, string $property, array $context = []): ?WriteMutator
    {
        $writeInfo = $this->writeInfoExtractor->getWriteInfo($target, $property, $context);

        if (null === $writeInfo) {
            return null;
        }

        if (PropertyWriteInfo::TYPE_NONE === $writeInfo->getType()) {
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
            $writeInfo = $writeInfo->getAdderInfo();
        }

        return new WriteMutator(
            $type,
            $writeInfo->getName(),
            PropertyReadInfo::VISIBILITY_PUBLIC !== $writeInfo->getVisibility()
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
}
