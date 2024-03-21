<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class DateTimeTransformerFactory extends AbstractUniqueTypeTransformerFactory implements PrioritizedTransformerFactoryInterface
{
    protected function createTransformer(Type $sourceType, Type $targetType, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        $isSourceDate = $this->isDateTimeType($sourceType);
        $isTargetDate = $this->isDateTimeType($targetType);

        if ($isSourceDate && $isTargetDate) {
            return $this->createTransformerForSourceAndTarget($sourceType, $targetType);
        }

        if ($isSourceDate) {
            return $this->createTransformerForSource($targetType, $source);
        }

        if ($isTargetDate) {
            return $this->createTransformerForTarget($sourceType, $targetType, $target);
        }

        return null;
    }

    protected function createTransformerForSourceAndTarget(Type $sourceType, Type $targetType): ?TransformerInterface
    {
        // if target is mutable
        if ($this->isDateTimeMutable($targetType)) {
            return new DateTimeInterfaceToMutableTransformer();
        }

        // if target is immutable or a generic DateTimeInterface
        return new DateTimeInterfaceToImmutableTransformer();
    }

    protected function createTransformerForSource(Type $targetType, SourcePropertyMetadata $metadata): ?TransformerInterface
    {
        if (Type::BUILTIN_TYPE_STRING === $targetType->getBuiltinType()) {
            return new DateTimeToStringTransformer($metadata->dateTimeFormat);
        }

        return null;
    }

    protected function createTransformerForTarget(Type $sourceType, Type $targetType, TargetPropertyMetadata $metadata): ?TransformerInterface
    {
        if (Type::BUILTIN_TYPE_STRING === $sourceType->getBuiltinType()) {
            return new StringToDateTimeTransformer($this->getClassName($targetType), $metadata->dateTimeFormat);
        }

        return null;
    }

    private function isDateTimeType(Type $type): bool
    {
        if (Type::BUILTIN_TYPE_OBJECT !== $type->getBuiltinType()) {
            return false;
        }

        if (\DateTimeInterface::class !== $type->getClassName() && !is_subclass_of($type->getClassName(), \DateTimeInterface::class)) {
            return false;
        }

        return true;
    }

    private function getClassName(Type $type): string
    {
        if (\DateTimeInterface::class === $type->getClassName() || $type->getClassName() === null) {
            return \DateTimeImmutable::class;
        }

        return $type->getClassName();
    }

    private function isDateTimeMutable(Type $type): bool
    {
        if (\DateTime::class !== $type->getClassName() && !is_subclass_of($type->getClassName(), \DateTime::class)) {
            return false;
        }

        return true;
    }

    public function getPriority(): int
    {
        return 16;
    }
}
