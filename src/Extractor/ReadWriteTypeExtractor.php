<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

/**
 * @internal
 */
final readonly class ReadWriteTypeExtractor implements PropertyTypeExtractorInterface
{
    public const READ_ACCESSOR = 'read_accessor';
    public const WRITE_MUTATOR = 'write_mutator';

    /**
     * @param array<string, mixed> $context
     */
    public function getTypes(string $class, string $property, array $context = []): ?array
    {
        if (($accessor = $context[self::READ_ACCESSOR] ?? false) && $accessor instanceof ReadAccessor) {
            return $accessor->getTypes($class);
        }

        if (($mutator = $context[self::WRITE_MUTATOR] ?? false) && $mutator instanceof WriteMutator) {
            return $mutator->getTypes($class);
        }

        return null;
    }
}
