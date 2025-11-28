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
use Symfony\Component\TypeInfo\TypeIdentifier;

final readonly class FromTargetCustomModelTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    public function supports(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        if (!$source->type->isIdentifiedBy(TypeIdentifier::ARRAY)) {
            return false;
        }

        if (!$target->type->isSatisfiedBy(fn (Type $type) => $type instanceof Type\ObjectType && $type->getClassName() === AddressDTO::class)) {
            return false;
        }

        return true;
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $addressDTO = new AddressDTO();
        $addressDTO->city = "{$value['city']} from custom model transformer";

        return $addressDTO;
    }
}
