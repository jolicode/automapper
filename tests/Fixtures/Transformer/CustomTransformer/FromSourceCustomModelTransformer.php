<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Tests\Fixtures\AddressDTO;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;
use Symfony\Component\TypeInfo\Type;

final readonly class FromSourceCustomModelTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    public function supports(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        if (!$source->type->isSatisfiedBy(fn (Type $type) => $type instanceof Type\ObjectType && $type->getClassName() === AddressDTO::class)) {
            return false;
        }

        if (!$target->type instanceof Type\CollectionType) {
            return false;
        }

        return true;
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        return [
            'city' => "{$value->city} set by custom model transformer",
            'street' => 'street set by custom model transformer',
        ];
    }

    /**
     * @param Type[] $targetTypes
     */
    private function targetIsArray(array $targetTypes): bool
    {
        foreach ($targetTypes as $targetType) {
            if ($targetType->getBuiltinType() === 'array') {
                return true;
            }
        }

        return false;
    }
}
