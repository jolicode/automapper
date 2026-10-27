<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator;

/**
 * The result set mapping cannot be hydrated by a generated hydrator, the ObjectHydrator is used instead.
 *
 * @internal
 */
final class UnsupportedResultSetMappingException extends \RuntimeException
{
}
