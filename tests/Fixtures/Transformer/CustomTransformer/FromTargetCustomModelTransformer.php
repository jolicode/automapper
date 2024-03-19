<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Tests\Fixtures\AddressDTO;
use AutoMapper\Transformer\CustomTransformer\CustomModelTransformerInterface;
use Symfony\Component\PropertyInfo\Type;

final readonly class FromTargetCustomModelTransformer implements CustomModelTransformerInterface
{
    public function supports(TypesMatching $types): bool
    {
        $sourceUniqueType = $types->getSourceUniqueType();

        if (null === $sourceUniqueType) {
            return false;
        }

        return $sourceUniqueType->getBuiltinType() === 'array' && $this->targetIsAddressDTO($types[$sourceUniqueType] ?? []);
    }

    /**
     * @param array $source
     */
    public function transform(object|array $source): mixed
    {
        $addressDTO = new AddressDTO();
        $addressDTO->city = "{$source['city']} from custom model transformer";

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
