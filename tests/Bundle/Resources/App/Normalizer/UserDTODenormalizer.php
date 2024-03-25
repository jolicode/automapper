<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Normalizer;

use AutoMapper\Tests\Bundle\Resources\App\Entity\UserDTO;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserDTODenormalizer implements DenormalizerInterface, NormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $user = new UserDTO();
        $user->id = $data['id'];
        $user->email = 'from_denormalizer';

        return $user;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === UserDTO::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [UserDTO::class => false];
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return [
            'id' => $object->id,
            'email' => 'from_normalizer',
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof UserDTO;
    }
}
