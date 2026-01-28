<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\ObjectMapper;

use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * @template T of object
 * @template T2 of object
 */
final readonly class TransformCallableTransformer implements PropertyTransformerInterface
{
    /**
     * @param TransformCallableInterface<T, T2> $transformCallable
     */
    public function __construct(
        private TransformCallableInterface $transformCallable,
    ) {
    }

    /**
     * @param T|array<mixed> $source
     *
     * @return ?T2
     */
    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        if (!\is_object($source)) {
            return null;
        }

        /** @var ?T2 $target */
        $target = \is_object($context['target'] ?? null) ? $context['target'] : null;

        /** @var ?T2 */
        return ($this->transformCallable)($value, $source, $target);
    }
}
