<?php

declare(strict_types=1);

namespace AutoMapper;

/**
 * Helper when using the AutoMapperRegistryAwareInterface.
 *
 * @internal
 */
trait AutoMapperRegistryAwareTrait
{
    protected AutoMapperRegistryInterface $autoMapperRegistry;

    public function setAutoMapperRegistry(AutoMapperRegistryInterface $autoMapperRegistry): void
    {
        $this->autoMapperRegistry = $autoMapperRegistry;
    }
}
