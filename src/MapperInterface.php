<?php

declare(strict_types=1);

namespace AutoMapper;

/**
 * Interface implemented by a single mapper.
 *
 * Each specific mapper should implements this interface
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @template Source of object|array<mixed>
 * @template Target of object|array<mixed>
 */
interface MapperInterface
{
    /**
     * @param Source               $value   Value to map
     * @param array<string, mixed> $context Options mapper have access to
     *
     * @return Target|null The mapped value
     */
    public function &map(mixed $value, array $context = []): mixed;
}
