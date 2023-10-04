<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Tests\Fixtures\Address;
use AutoMapper\Tests\Fixtures\AddressDTO;
use AutoMapper\Transformer\CustomTransformer\CustomModelTransformerInterface;
use Symfony\Component\PropertyInfo\Type;

final readonly class SourceTargetCustomModelTransformer implements CustomModelTransformerInterface
{
    public function supports(array $sourceTypes, array $targetTypes): bool
    {
        return $this->sourceIsAddressDTO($sourceTypes) && $this->targetIsAddress($targetTypes);
    }

    /**
     * @param AddressDTO $input
     */
    public function transform(mixed $input): mixed
    {
        $input->city = "{$input->city} from custom model transformer";

        return \AutoMapper\Tests\Fixtures\Address::fromDTO($input);
    }

    /**
     * @param Type[] $sourceTypes
     */
    private function sourceIsAddressDTO(array $sourceTypes): bool
    {
        foreach ($sourceTypes as $sourceType) {
            if ($sourceType->getClassName() === AddressDTO::class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Type[] $targetTypes
     */
    private function targetIsAddress(array $targetTypes): bool
    {
        foreach ($targetTypes as $targetType) {
            if ($targetType->getClassName() === Address::class) {
                return true;
            }
        }

        return false;
    }
}
