<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\CustomTransformer\CustomModelTransformerInterface;
use AutoMapper\Tests\Fixtures\AddressDTO;
use Symfony\Component\PropertyInfo\Type;

final readonly class FromTargetCustomModelTransformer implements CustomModelTransformerInterface
{
    public function supports(array $sourceTypes, array $targetTypes): bool
    {
        return $this->sourceIsArray($sourceTypes) && $this->targetIsAddressDTO($targetTypes);
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
     * @param Type[] $sourceTypes
     */
    private function sourceIsArray(array $sourceTypes): bool
    {
        foreach ($sourceTypes as $sourceType) {
            if ($sourceType->getBuiltinType() === 'array') {
                return true;
            }
        }

        return false;
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
