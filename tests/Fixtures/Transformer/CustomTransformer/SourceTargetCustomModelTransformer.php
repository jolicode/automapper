<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Tests\Fixtures\Address;
use AutoMapper\Tests\Fixtures\AddressDTO;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;
use Symfony\Component\PropertyInfo\Type;

final readonly class SourceTargetCustomModelTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        $sourceUniqueType = $types->getSourceUniqueType();

        if (null === $sourceUniqueType) {
            return false;
        }

        return $sourceUniqueType->getClassName() === AddressDTO::class && $this->targetIsAddress($types[$sourceUniqueType] ?? []);
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $value->city = "{$value->city} from custom model transformer";

        return Address::fromDTO($value);
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
