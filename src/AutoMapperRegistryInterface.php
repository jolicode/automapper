<?php

declare(strict_types=1);

namespace AutoMapper;

/**
 * Allows to retrieve a mapper.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
interface AutoMapperRegistryInterface
{
    /**
     * Gets a specific mapper for a source type and a target type.
     */
    public function getMapper(string $source, string $target): MapperInterface;
}
