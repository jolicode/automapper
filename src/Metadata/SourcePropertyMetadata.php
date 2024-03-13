<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Extractor\ReadAccessor;
use Symfony\Component\PropertyInfo\Type;

/**
 * Source Property metadata.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final readonly class SourcePropertyMetadata
{
    /**
     * @param Type[]        $types
     * @param string[]|null $groups
     */
    public function __construct(
        public array $types,
        public string $name,
        public ?ReadAccessor $accessor = null,
        public bool $checkExists = false,
        public ?array $groups = null,
        public bool $ignored = false,
        public bool $isPublic = false,
        public string $dateTimeFormat = \DateTimeInterface::RFC3339,
    ) {
    }

    /**
     * @param Type[] $types
     */
    public function withTypes(array $types): self
    {
        return new self($types, $this->name, $this->accessor, $this->checkExists, $this->groups, $this->ignored, $this->isPublic);
    }
}
