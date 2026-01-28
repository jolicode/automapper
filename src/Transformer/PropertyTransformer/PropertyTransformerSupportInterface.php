<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\PropertyTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;

/**
 * Allow to automatically register a transformer for a specific property.
 *
 * @experimental
 */
interface PropertyTransformerSupportInterface extends PropertyTransformerInterface
{
    /**
     * When implemented with a PropertyTransformerInterface, this method is called to check if the transformer supports the given properties.
     *
     * This method is not need if the transformer is set in an #[MapTo] or #[MapFrom] attribute
     *
     * @param SourcePropertyMetadata $source         The source property metadata
     * @param TargetPropertyMetadata $target         The target property metadata
     * @param MapperMetadata         $mapperMetadata The mapper metadata
     */
    public function supports(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool;
}
