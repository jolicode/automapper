<?php

declare(strict_types=1);

namespace AutoMapper;

/**
 * Value object types handled by their own dedicated Symfony normalizer or value transformer.
 *
 * The AutoMapper must not claim them, otherwise it would produce a structure dump
 * (`{"name":"FLAT","value":"flat"}`) instead of the expected representation (`"flat"`).
 *
 * @internal
 */
final class ValueObjectTypes
{
    /** @var list<class-string> */
    public const array UNSUPPORTED = [
        \DateTimeInterface::class,
        \DateTimeZone::class,
        \DateInterval::class,
        \UnitEnum::class,
        \Symfony\Component\Uid\AbstractUid::class,
    ];

    /**
     * @param class-string $className
     */
    public static function isUnsupported(string $className): bool
    {
        foreach (self::UNSUPPORTED as $unsupported) {
            if (is_a($className, $unsupported, true)) {
                return true;
            }
        }

        return false;
    }
}
