<?php

namespace AutoMapper\Transformer\ObjectMapper;

use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

final readonly class TransformCallableTransformer implements PropertyTransformerInterface
{
    public function __construct(private TransformCallableInterface $transformCallable)
    {
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        if (!\is_object($source)) {
            return null;
        }

        return ($this->transformCallable)($value, $source, \is_object($context['target'] ?? null) ? $context['target'] : null);
    }
}