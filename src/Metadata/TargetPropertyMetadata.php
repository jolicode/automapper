<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Event\TargetPropertyMetadata as EventTargetPropertyMetadata;
use AutoMapper\Extractor\WriteMutator;

/**
 * Target Property metadata.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @experimental
 */
final readonly class TargetPropertyMetadata
{
    /**
     * @param string[]|null $groups
     */
    public function __construct(
        public string $name,
        public ?WriteMutator $writeMutator = null,
        public ?WriteMutator $writeMutatorConstructor = null,
        public ?array $groups = null,
        public string $dateTimeFormat = \DateTimeInterface::RFC3339,
    ) {
    }

    public static function fromEvent(EventTargetPropertyMetadata $metadata): self
    {
        return new self(
            $metadata->name,
            $metadata->writeMutator,
            $metadata->writeMutatorConstructor,
            $metadata->groups,
            $metadata->dateTimeFormat ?? \DateTimeInterface::RFC3339,
        );
    }
}
