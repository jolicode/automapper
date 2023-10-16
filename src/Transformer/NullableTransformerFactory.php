<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\MapperMetadataInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class NullableTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    public function __construct(
        private ChainTransformerFactory $chainTransformerFactory,
    ) {
    }

    public function getTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata): ?TransformerInterface
    {
        $nbSourceTypes = $sourceTypes ? \count($sourceTypes) : 0;

        if (null === $sourceTypes || 0 === $nbSourceTypes || $nbSourceTypes > 1) {
            return null;
        }

        $propertyType = $sourceTypes[0];

        if (!$propertyType->isNullable()) {
            return null;
        }

        $isTargetNullable = false;

        foreach ($targetTypes ?? [] as $targetType) {
            if ($targetType->isNullable()) {
                $isTargetNullable = true;

                break;
            }
        }

        $subTransformer = $this->chainTransformerFactory->getTransformer([new Type(
            $propertyType->getBuiltinType(),
            false,
            $propertyType->getClassName(),
            $propertyType->isCollection(),
            $propertyType->getCollectionKeyTypes(),
            $propertyType->getCollectionValueTypes()
        )], $targetTypes, $mapperMetadata);

        if (null === $subTransformer) {
            return null;
        }

        // Remove nullable property here to avoid infinite loop
        return new NullableTransformer($subTransformer, $isTargetNullable);
    }

    public function getPriority(): int
    {
        return 64;
    }
}
