<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Service;

use AutoMapper\Tests\Bundle\Resources\App\Entity\User;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class IdNameConverter implements NameConverterInterface
{
    public function normalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        if ($class === User::class && 'id' === $propertyName) {
            return '@id';
        }

        return $propertyName;
    }

    public function denormalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        if ($class === User::class && '@id' === $propertyName) {
            return 'id';
        }

        return $propertyName;
    }
}
