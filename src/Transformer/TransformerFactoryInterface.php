<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use Symfony\Component\PropertyInfo\Type;

/**
 * Create transformer.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
interface TransformerFactoryInterface
{
    /**
     * Get transformer to use when mapping from an array of type to another array of type.
     */
    public function getTransformer(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface;
}
