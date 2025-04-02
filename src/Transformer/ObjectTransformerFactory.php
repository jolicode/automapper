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
final class ObjectTransformerFactory extends AbstractUniqueTypeTransformerFactory implements PrioritizedTransformerFactoryInterface
{
    protected function createTransformer(Type $sourceType, Type $targetType, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        // Only deal with source type being an object or an array that is not a collection
        if (!$this->isObjectType($sourceType) || !$this->isObjectType($targetType)) {
            return null;
        }

        $sourceTypeName = 'array';
        $targetTypeName = 'array';

        if (Type::BUILTIN_TYPE_OBJECT === $sourceType->getBuiltinType()) {
            $sourceTypeName = $sourceType->getClassName();
        }

        if (Type::BUILTIN_TYPE_OBJECT === $targetType->getBuiltinType()) {
            $targetTypeName = $targetType->getClassName();
        }

        if (null !== $sourceTypeName && null !== $targetTypeName) {
            return new ObjectTransformer($sourceType, $targetType);
        }

        return null;
    }

    private function isObjectType(Type $type): bool
    {
        if (!\in_array($type->getBuiltinType(), [Type::BUILTIN_TYPE_OBJECT, Type::BUILTIN_TYPE_ARRAY])) {
            return false;
        }

        if (Type::BUILTIN_TYPE_ARRAY === $type->getBuiltinType() && $type->isCollection()) {
            return false;
        }

        if ($type->getClassName() !== null && is_subclass_of($type->getClassName(), \UnitEnum::class)) {
            return false;
        }

        /** @var class-string|null $class */
        $class = $type->getClassName();

        if ($class === null || $class === \stdClass::class) {
            return true;
        }

        if (!class_exists($class) && !interface_exists($class)) {
            return false;
        }

        $reflectionClass = new \ReflectionClass($class);

        if ($reflectionClass->isInternal()) {
            return false;
        }

        return true;
    }

    public function getPriority(): int
    {
        return 2;
    }
}
