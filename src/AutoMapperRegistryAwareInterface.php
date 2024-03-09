<?php

declare(strict_types=1);

namespace AutoMapper;

/**
 * Allows to use a AutoMapperRegistry.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
interface AutoMapperRegistryAwareInterface
{
    public function setAutoMapperRegistry(AutoMapperRegistryInterface $autoMapperRegistry): void;
}
