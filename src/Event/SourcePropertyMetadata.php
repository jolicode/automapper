<?php

declare(strict_types=1);

namespace AutoMapper\Event;

use AutoMapper\Extractor\ReadAccessor;
use Symfony\Component\TypeInfo\Type;

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
     * @param string[]|null $groups
     */
    public function __construct(
        public string $property,
        public ?ReadAccessor $accessor = null,
        public ?bool $checkExists = null,
        public bool $extractGroupsIfNull = true,
        public ?array $groups = null,
        public ?string $dateTimeFormat = null,
        public ?Type $type = null,
    ) {
    }
}
