<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\MapperMetadataInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Create property transformer.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
interface TransformerPropertyFactoryInterface
{
    /**
     * Get transformer to use when mapping from an array of type to another array of type.
     *
     * @param Type[]|null $sourceTypes
     * @param Type[]|null $targetTypes
     */
    public function getPropertyTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata, string $property): ?TransformerInterface;
}
