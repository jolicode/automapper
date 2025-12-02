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
final class ObjectTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        if ($source->type === null || $target->type === null) {
            return null;
        }

        // Only deal with source type being an object or an array that is not a collection
        if (!$this->isObjectType($source->type) || !$this->isObjectType($target->type)) {
            return null;
        }

        // Check that we have at least one object correctly defined
        if (!$source->type->isIdentifiedBy(TypeIdentifier::OBJECT) && !$target->type->isIdentifiedBy(TypeIdentifier::OBJECT)) {
            return null;
        }

        return new ObjectTransformer($source->type, $target->type);
    }

    private function isObjectType(Type $type): bool
    {
        if (!$type->isIdentifiedBy(TypeIdentifier::OBJECT) && !$type->isIdentifiedBy(TypeIdentifier::ARRAY) && !$type->isIdentifiedBy(TypeIdentifier::MIXED)) {
            return false;
        }

        if ($type instanceof Type\ObjectType) {
            $className = $type->getClassName();

            if (is_subclass_of($className, \UnitEnum::class)) {
                return false;
            }

            if (!class_exists($className) && !interface_exists($className)) {
                return false;
            }

            if ($className !== \stdClass::class) {
                $reflectionClass = new \ReflectionClass($className);

                if ($reflectionClass->isInternal()) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getPriority(): int
    {
        return 2;
    }
}
