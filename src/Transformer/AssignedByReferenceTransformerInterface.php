<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
interface AssignedByReferenceTransformerInterface
{
    /**
     * Should the resulting output be assigned by ref.
     */
    public function assignByRef(): bool;
}
