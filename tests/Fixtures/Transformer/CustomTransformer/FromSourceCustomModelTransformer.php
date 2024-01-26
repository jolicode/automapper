<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\CustomTransformer\CustomModelTransformerInterface;
use AutoMapper\Tests\Fixtures\AddressDTO;
use Symfony\Component\PropertyInfo\Type;

final readonly class FromSourceCustomModelTransformer implements CustomModelTransformerInterface
{
    public function supports(array $sourceTypes, array $targetTypes): bool
    {
        return $this->sourceIsAddressDTO($sourceTypes) && $this->targetIsArray($targetTypes);
    }

    public function transform(object|array $source): mixed
    {
        return [
            'city' => "{$source->city} set by custom model transformer",
            'street' => 'street set by custom model transformer',
        ];
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
