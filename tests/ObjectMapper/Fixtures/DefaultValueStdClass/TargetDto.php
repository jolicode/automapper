<?php

declare(strict_types=1);

namespace AutoMapper\Tests\ObjectMapper\Fixtures\DefaultValueStdClass;

use Symfony\Component\ObjectMapper\Attribute\Map;

class TargetDto
{
    public function __construct(
        public string $id,
        #[Map(source: 'optional', if: [self::class, 'isDefined'])]
        public ?string $optional = null,
    ) {
    }

    public static function isDefined($source): bool
    {
        return isset($source);
    }
}
