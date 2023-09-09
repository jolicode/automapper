<?php

namespace AutoMapper\Transformer;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
interface PrioritizedTransformerFactoryInterface
{
    /**
     * TransformerFactory priority.
     */
    public function getPriority(): int;
}
