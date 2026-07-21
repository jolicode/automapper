<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class DateTimeTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        $isSourceDate = $this->isDateTimeType($source->type);
        $isTargetDate = $this->isDateTimeType($target->type);

        if ($isSourceDate && $isTargetDate) {
            return $this->createTransformerForSourceAndTarget($target->type);
        }

        if ($isSourceDate) {
            return $this->createTransformerForSource($target->type, $source);
        }

        if ($isTargetDate) {
            return $this->createTransformerForTarget($source->type, $target->type, $target);
        }

        return null;
    }

    private function createTransformerForSourceAndTarget(?Type $targetType): TransformerInterface
    {
        // if target is mutable
        if ($this->isDateTimeMutable($targetType)) {
            return new DateTimeInterfaceToMutableTransformer($this->getConcreteClassName($targetType, \DateTime::class));
        }

        // if target is immutable or a generic DateTimeInterface
        return new DateTimeInterfaceToImmutableTransformer($this->getConcreteClassName($targetType, \DateTimeImmutable::class));
    }

    /**
     * @param class-string $default
     *
     * @return class-string
     */
    private function getConcreteClassName(?Type $targetType, string $default): string
    {
        // \DateTimeInterface cannot be instantiated, fall back to the default concrete class,
        // otherwise keep the concrete target class (custom subclasses included)
        if (!$targetType instanceof Type\ObjectType || \DateTimeInterface::class === $targetType->getClassName()) {
            return $default;
        }

        /** @var class-string */
        return $targetType->getClassName();
    }

    private function createTransformerForSource(?Type $targetType, SourcePropertyMetadata $metadata): ?TransformerInterface
    {
        if ($targetType !== null && $targetType->isIdentifiedBy(TypeIdentifier::STRING)) {
            return new DateTimeToStringTransformer($metadata->dateTimeFormat);
        }

        return null;
    }

    private function createTransformerForTarget(?Type $sourceType, ?Type $targetType, TargetPropertyMetadata $metadata): ?TransformerInterface
    {
        if ($sourceType !== null && $sourceType->isIdentifiedBy(TypeIdentifier::STRING)) {
            return new StringToDateTimeTransformer($this->getClassName($targetType), $metadata->dateTimeFormat);
        }

        return null;
    }

    private function isDateTimeType(?Type $type): bool
    {
        if (!$type instanceof Type\ObjectType) {
            return false;
        }

        if (\DateTimeInterface::class !== $type->getClassName() && !is_subclass_of($type->getClassName(), \DateTimeInterface::class)) {
            return false;
        }

        return true;
    }

    private function getClassName(?Type $type): string
    {
        if (!$type instanceof Type\ObjectType) {
            return \DateTimeImmutable::class;
        }

        if (\DateTimeInterface::class === $type->getClassName()) {
            return \DateTimeImmutable::class;
        }

        return $type->getClassName();
    }

    private function isDateTimeMutable(?Type $type): bool
    {
        if (!$type instanceof Type\ObjectType) {
            return false;
        }

        if (\DateTime::class !== $type->getClassName() && !is_subclass_of($type->getClassName(), \DateTime::class)) {
            return false;
        }

        return true;
    }

    public function getPriority(): int
    {
        return 32;
    }
}
