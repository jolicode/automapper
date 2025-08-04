<?php

declare(strict_types=1);

namespace AutoMapper\Exception;

use AutoMapper\MapperContext;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class ReadOnlyTargetException extends RuntimeException
{
    public function __construct(int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Cannot use readonly class as an object to populate. You can opt-out this behavior by using the context "%s"', MapperContext::ALLOW_READONLY_TARGET_TO_POPULATE), $code, $previous);
    }
}
