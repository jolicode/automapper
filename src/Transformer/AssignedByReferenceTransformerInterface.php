<?php

namespace AutoMapper\Transformer;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
interface AssignedByReferenceTransformerInterface
{
    /**
     * Should the resulting output be assigned by ref.
     */
    public function assignByRef(): bool;
}
