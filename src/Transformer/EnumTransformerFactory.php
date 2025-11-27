<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
final class EnumTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        // source is enum, target isn't
        if ($this->isEnumType($source->type, true) && !$this->isEnumType($target->type)) {
            return new SourceEnumTransformer();
        }

        // target is enum, source isn't
        if (!$this->isEnumType($source->type) && $this->isEnumType($target->type, true)) {
            // @phpstan-ignore-next-line
            return new TargetEnumTransformer($target->type->getClassName());
        }

        // both source & target are enums
        if ($this->isEnumType($source->type) && $this->isEnumType($target->type)) {
            return new CopyEnumTransformer();
        }

        return null;
    }

    private function isEnumType(?Type $type, bool $backed = false): bool
    {
        if (!$type instanceof Type\EnumType) {
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
