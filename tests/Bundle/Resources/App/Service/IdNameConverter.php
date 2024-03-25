<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Service;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

if (Kernel::MAJOR_VERSION < 6) {
    class IdNameConverter implements AdvancedNameConverterInterface
    {
        public function normalize($propertyName, ?string $class = null, ?string $format = null, array $context = []): string
        {
            if ('id' === $propertyName) {
                return '@id';
            }

            return $propertyName;
        }

        public function denormalize($propertyName, ?string $class = null, ?string $format = null, array $context = []): string
        {
            if ('@id' === $propertyName) {
                return 'id';
            }

            return $propertyName;
        }
    }
} else {
    class IdNameConverter implements AdvancedNameConverterInterface
    {
        public function normalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
        {
            if ('id' === $propertyName) {
                return '@id';
            }

            return $propertyName;
        }

        public function denormalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
        {
            if ('@id' === $propertyName) {
                return 'id';
            }

            return $propertyName;
        }
    }
}
