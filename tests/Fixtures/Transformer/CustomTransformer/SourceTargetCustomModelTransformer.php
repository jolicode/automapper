<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Tests\Fixtures\Address;
use AutoMapper\Tests\Fixtures\AddressDTO;
use AutoMapper\Transformer\CustomTransformer\CustomModelTransformerInterface;
use Symfony\Component\PropertyInfo\Type;

final readonly class SourceTargetCustomModelTransformer implements CustomModelTransformerInterface
{
    public function supports(TypesMatching $types): bool
    {
        $sourceUniqueType = $types->getSourceUniqueType();

        if (null === $sourceUniqueType) {
            return false;
        }

        return $sourceUniqueType->getClassName() === AddressDTO::class && $this->targetIsAddress($types[$sourceUniqueType] ?? []);
    }

    /**
     * @param AddressDTO $source
     */
    public function transform(object|array $source): mixed
    {
        $source->city = "{$source->city} from custom model transformer";

        return Address::fromDTO($source);
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
