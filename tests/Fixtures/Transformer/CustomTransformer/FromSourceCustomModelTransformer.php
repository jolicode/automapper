<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Tests\Fixtures\AddressDTO;
use AutoMapper\Transformer\CustomTransformer\CustomModelTransformerInterface;
use Symfony\Component\PropertyInfo\Type;

final readonly class FromSourceCustomModelTransformer implements CustomModelTransformerInterface
{
    public function supports(TypesMatching $types): bool
    {
        $sourceUniqueType = $types->getSourceUniqueType();

        if (null === $sourceUniqueType) {
            return false;
        }

        return $sourceUniqueType->getClassName() === AddressDTO::class && $this->targetIsArray($types[$sourceUniqueType] ?? []);
    }

    public function transform(object|array $source): mixed
    {
        return [
            'city' => "{$source->city} set by custom model transformer",
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
