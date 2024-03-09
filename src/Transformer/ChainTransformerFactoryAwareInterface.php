<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

/**
 * Allows to use a chain transformer factory.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
interface ChainTransformerFactoryAwareInterface
{
    public function setChainTransformerFactory(ChainTransformerFactory $chainTransformerFactory): void;
}
