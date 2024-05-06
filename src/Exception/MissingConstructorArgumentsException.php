<?php

declare(strict_types=1);

namespace AutoMapper\Exception;

final class MissingConstructorArgumentsException extends RuntimeException
{
    /**
     * @param string[]          $missingArguments
     * @param class-string|null $class
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly array $missingArguments = [],
        public readonly ?string $class = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
