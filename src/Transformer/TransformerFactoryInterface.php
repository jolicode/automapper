<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\MapperMetadataInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Create transformer.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
interface TransformerFactoryInterface
{
    /**
     * Get transformer to use when mapping from an array of type to another array of type.
     *
     * @param Type[]|null $sourceTypes
     * @param Type[]|null $targetTypes
     */
    public function getTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata): ?TransformerInterface;
}
