<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\PropertyTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;

interface PropertyTransformerComputeInterface extends PropertyTransformerSupportInterface
{
    /**
     * When implemented with a PropertyTransformerSupportInterface, this method is called to compute a value that would be passed to the `transform` method.
     *
     * This value is exported by using `var_export` and used directly in the generated code.
     *
     * @param SourcePropertyMetadata $source         The source property metadata
     * @param TargetPropertyMetadata $target         The target property metadata
     * @param MapperMetadata         $mapperMetadata The mapper metadata
     */
    public function compute(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): mixed;

    /**
     * @param mixed                       $value    the value of the property to transform, can be null if there is no way to read the data from the mapping
     * @param object|array<string, mixed> $source   the source input on which the custom transformation applies
     * @param array<string, mixed>        $context  Context during mapping
     * @param mixed                       $computed The computed value from PropertyTransformerComputeInterface, if applicable, otherwise null
     */
    public function transform(mixed $value, object|array $source, array $context, mixed $computed = null): mixed;
}
