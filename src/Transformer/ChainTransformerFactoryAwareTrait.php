<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

trait ChainTransformerFactoryAwareTrait
{
    protected ChainTransformerFactory $chainTransformerFactory;

    public function setChainTransformerFactory(ChainTransformerFactory $chainTransformerFactory): void
    {
        $this->chainTransformerFactory = $chainTransformerFactory;
    }
}
