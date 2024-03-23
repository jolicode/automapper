<?php

declare(strict_types=1);

namespace AutoMapper;

/**
 * An auto mapper has the role of mapping a source to a target.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @phpstan-import-type MapperContextArray from MapperContext
 */
interface AutoMapperInterface
{
    /**
     * @template Source of object
     * @template Target of object
     *
     * @param Source|array<mixed>                              $source
     * @param class-string<Target>|'array'|array<mixed>|Target $target
     * @param MapperContextArray                               $context
     *
     * @return ($target is class-string|Target ? Target|null : array<mixed>|null)
     */
    public function map(array|object $source, string|array|object $target, array $context = []): array|object|null;
}
