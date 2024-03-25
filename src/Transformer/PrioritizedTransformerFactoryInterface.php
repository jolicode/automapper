<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
interface PrioritizedTransformerFactoryInterface
{
    /**
     * TransformerFactory priority.
     */
    public function getPriority(): int;
}
