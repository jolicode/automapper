<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\ApiPlatform;

use ApiPlatform\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

final readonly class JsonLdIdTransformer implements PropertyTransformerInterface
{
    public function __construct(
        private LegacyIriConverterInterface|IriConverterInterface $iriConverter,
    ) {
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        if (\is_array($source)) {
            return null;
        }

        return $this->iriConverter->getIriFromResource($source);
    }
}
