<?php

declare(strict_types=1);

namespace AutoMapper\Event;

use AutoMapper\Extractor\ReadAccessor;
use Symfony\Component\PropertyInfo\Type;

/**
 * Source Property metadata for event.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class SourcePropertyMetadata
{
    /**
     * @param Type[]|null   $types
     * @param string[]|null $groups
     */
    public function __construct(
        public string $name,
        public ?array $types = null,
        public ?ReadAccessor $accessor = null,
        public ?bool $checkExists = null,
        public bool $extractGroupsIfNull = true,
        public ?array $groups = null,
        public ?string $dateTimeFormat = null,
    ) {
    }
}
