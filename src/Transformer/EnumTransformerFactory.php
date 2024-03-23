<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
final class EnumTransformerFactory extends AbstractUniqueTypeTransformerFactory implements PrioritizedTransformerFactoryInterface
{
    protected function createTransformer(Type $sourceType, Type $targetType, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        // source is enum, target isn't
        if ($this->isEnumType($sourceType, true) && !$this->isEnumType($targetType)) {
            return new SourceEnumTransformer();
        }

        // target is enum, source isn't
        if (!$this->isEnumType($sourceType) && $this->isEnumType($targetType, true)) {
            // @phpstan-ignore-next-line
            return new TargetEnumTransformer($targetType->getClassName());
        }

        // both source & target are enums
        if ($this->isEnumType($sourceType) && $this->isEnumType($targetType)) {
            return new CopyEnumTransformer();
        }

        return null;
    }

    private function isEnumType(Type $type, bool $backed = false): bool
    {
        if (Type::BUILTIN_TYPE_OBJECT !== $type->getBuiltinType()) {
            return false;
        }

        if ($type->getClassName() === null) {
            return false;
        }

        if (!is_subclass_of($type->getClassName(), \UnitEnum::class)) {
            return false;
        }

        if ($backed && !is_subclass_of($type->getClassName(), \BackedEnum::class)) {
            return false;
        }

        return true;
    }

    public function getPriority(): int
    {
        return 3;
    }
}
