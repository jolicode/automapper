<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\ApiPlatform;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

final readonly class JsonLdIdTransformer implements PropertyTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter
    ) {
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        if (\is_array($source)) {
            return null;
        }

        $operation = $context['operation'] ?? null;

        return $this->iriConverter->getIriFromResource($source, operation: $operation instanceof Operation ? $operation : null);
    }
}
