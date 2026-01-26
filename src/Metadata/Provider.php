<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

class Provider
{
    public const string TYPE_CALLABLE = 'callable';
    public const string TYPE_SERVICE = 'service';
    public const string TYPE_SERVICE_CALLABLE = 'service_callable';

    public function __construct(
        /** @var self::TYPE_* */
        public readonly string $type,
        public readonly string $value,
        public readonly bool $isFromObjectMapper = false,
    ) {
    }
}
