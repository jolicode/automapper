<?php

declare(strict_types=1);

namespace AutoMapper\Event;

use AutoMapper\Extractor\ReadAccessorInterface;
use AutoMapper\Extractor\WriteMutatorInterface;
use Symfony\Component\TypeInfo\Type;

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
        public string $property,
        public ?array $types = null,
        public ?ReadAccessorInterface $readAccessor = null,
        public ?WriteMutatorInterface $writeMutator = null,
        public ?string $parameterInConstructor = null,
        public bool $extractGroupsIfNull = true,
        public ?array $groups = null,
        public ?string $dateTimeFormat = null,
        public ?Type $type = null,
    ) {
    }
}
