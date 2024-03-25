<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
interface DependentTransformerInterface
{
    /**
     * Get dependencies for this transformer.
     *
     * @return MapperDependency[]
     */
    public function getDependencies(): array;
}
