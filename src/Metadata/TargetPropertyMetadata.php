<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Event\TargetPropertyMetadata as EventTargetPropertyMetadata;
use AutoMapper\Extractor\WriteMutator;
use Symfony\Component\PropertyInfo\Type;

/**
 * Target Property metadata.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final readonly class TargetPropertyMetadata
{
    /**
     * @param Type[]        $types
     * @param string[]|null $groups
     */
    public function __construct(
        public array $types,
        public string $name,
        public ?WriteMutator $writeMutator = null,
        public ?WriteMutator $writeMutatorConstructor = null,
        public ?array $groups = null,
        public string $dateTimeFormat = \DateTimeInterface::RFC3339,
    ) {
    }

    /**
     * @param Type[] $types
     */
    public function withTypes(array $types): self
    {
        return new self($types, $this->name, $this->writeMutator, $this->writeMutatorConstructor, $this->groups, $this->dateTimeFormat);
    }

    public static function fromEvent(EventTargetPropertyMetadata $metadata): self
    {
        return new self(
            $metadata->types ?? [],
            $metadata->name,
            $metadata->writeMutator,
            $metadata->writeMutatorConstructor,
            $metadata->groups,
            $metadata->dateTimeFormat ?? \DateTimeInterface::RFC3339,
        );
    }
}
