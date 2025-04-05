<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

/**
 * Allow to extract types from accessor or mutator.
 *
 * This extractor is a temporary fix for the lack of a proper way to extract types from only an accessor or mutator in
 * symfony, a proper fix would be to use the PropertyInfo component from symfony in this case.
 *
 * @internal
 */
final readonly class ReadWriteTypeExtractor implements PropertyTypeExtractorInterface
{
    public const READ_ACCESSOR = 'read_accessor';
    public const WRITE_MUTATOR = 'write_mutator';
    public const EXTRACT_TYPE_FROM_GETTER = 'extract_type_from_getter';

    /**
     * @param array<string, mixed> $context
     */
    public function getTypes(string $class, string $property, array $context = []): ?array
    {
        if (($accessor = $context[self::READ_ACCESSOR] ?? false) && $accessor instanceof ReadAccessor) {
            return $accessor->getTypes($class);
        }

        if (!($context[self::EXTRACT_TYPE_FROM_GETTER] ?? false) && ($mutator = $context[self::WRITE_MUTATOR] ?? false) && $mutator instanceof WriteMutator) {
            return $mutator->getTypes($class);
        }

        return null;
    }
}
