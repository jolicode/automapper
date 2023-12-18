<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Tests\Fixtures\AddressDTO;
use AutoMapper\Transformer\CustomTransformer\CustomModelTransformerInterface;
use Symfony\Component\PropertyInfo\Type;

final readonly class FromTargetCustomModelTransformer implements CustomModelTransformerInterface
{
    public function supports(array $sourceTypes, array $targetTypes): bool
    {
        return $this->sourceIsArray($sourceTypes) && $this->targetIsAddressDTO($targetTypes);
    }

    public function transform(mixed $input): mixed
    {
        $addressDTO = new \AutoMapper\Tests\Fixtures\AddressDTO();
        $addressDTO->city = "{$input['city']} from custom model transformer";

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
