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
     * @template Source of object
     * @template Target of object
     *
     * @param class-string<Source>|'array' $source
     * @param class-string<Target>|'array' $target
     *
     * @return ($source is class-string ? ($target is 'array' ? MapperInterface<Source, array<mixed>> : MapperInterface<Source, Target>) : MapperInterface<array<mixed>, Target>)
     */
    public function getMapper(string $source, string $target): MapperInterface;
}
