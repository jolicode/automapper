<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Tests\Fixtures\AddressDTO;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;
use Symfony\Component\PropertyInfo\Type;

final readonly class FromTargetCustomModelTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        $sourceUniqueType = $types->getSourceUniqueType();

        if (null === $sourceUniqueType) {
            return false;
        }

        return $sourceUniqueType->getBuiltinType() === 'array' && $this->targetIsAddressDTO($types[$sourceUniqueType] ?? []);
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $addressDTO = new AddressDTO();
        $addressDTO->city = "{$value['city']} from custom model transformer";

        return $addressDTO;
    }

    /**
     * @param Type[] $targetTypes
     */
    private function targetIsAddressDTO(array $targetTypes): bool
    {
        foreach ($targetTypes as $targetType) {
            if ($targetType->getClassName() === AddressDTO::class) {
                return true;
            }
        }

        return false;
    }
}
