<?php

declare(strict_types=1);

namespace AutoMapper;

/**
 * An auto mapper has the role of mapping a source to a target.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
interface AutoMapperInterface
{
    /**
     * Maps data from a source to a target.
     *
     * @param array|object                      $source  Any data object, which may be an object or an array
     * @param 'array'|class-string|array|object $target  To which type of data, or data, the source should be mapped
     * @param array                             $context Mapper context
     *
     * @return array|object|null The mapped object
     */
    public function map(array|object $source, string|array|object $target, array $context = []): null|array|object;
}
