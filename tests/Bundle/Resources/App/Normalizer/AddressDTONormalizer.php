<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Normalizer;

use AutoMapper\Tests\Bundle\Resources\App\Entity\AddressDTO;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AddressDTONormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        throw new \RuntimeException('Should not be called');
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === AddressDTO::class;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        throw new \RuntimeException('Should not be called');
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AddressDTO;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [AddressDTO::class => true];
    }
}
