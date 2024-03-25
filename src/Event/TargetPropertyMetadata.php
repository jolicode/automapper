<?php

declare(strict_types=1);

namespace AutoMapper\Event;

use AutoMapper\Extractor\WriteMutator;
use Symfony\Component\PropertyInfo\Type;

/**
 * Target Property metadata for event.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class TargetPropertyMetadata
{
    /**
     * @param Type[]|null   $types
     * @param string[]|null $groups
     */
    public function __construct(
        public string $name,
        public ?array $types = null,
        public ?WriteMutator $writeMutator = null,
        public ?WriteMutator $writeMutatorConstructor = null,
        public bool $extractGroupsIfNull = true,
        public ?array $groups = null,
        public ?string $dateTimeFormat = null,
    ) {
    }
}
