<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Event\TargetPropertyMetadata as EventTargetPropertyMetadata;
use AutoMapper\Extractor\ReadAccessor;
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
        public string $property,
        public ?ReadAccessor $readAccessor = null,
        public ?WriteMutator $writeMutator = null,
        public ?string $parameterInConstructor = null,
        public ?array $groups = null,
        public string $dateTimeFormat = \DateTimeInterface::RFC3339,
    ) {
    }

    public static function fromEvent(EventTargetPropertyMetadata $metadata): self
    {
        return new self(
            $metadata->property,
            $metadata->readAccessor,
            $metadata->writeMutator,
            $metadata->parameterInConstructor,
            $metadata->groups,
            $metadata->dateTimeFormat ?? \DateTimeInterface::RFC3339,
        );
    }
}
