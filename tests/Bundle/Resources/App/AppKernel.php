<?php

declare(strict_types=1);

namespace DummyApp;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Symfony\Bundle\AutoMapperBundle;
use AutoMapper\Tests\Bundle\Fixtures\User;
use AutoMapper\Tests\Bundle\Fixtures\UserDTO;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        $bundles = [
            new FrameworkBundle(),
            new AutoMapperBundle(),
        ];

        return $bundles;
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config.yml');
    }

    public function getProjectDir(): string
    {
        return __DIR__ . '/..';
    }
}

class YearOfBirthTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    public function transform(mixed $value, object|array $user, array $context): mixed
    {
        \assert($user instanceof User);

        return ((int) date('Y')) - ((int) $user->age);
    }

    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        return User::class === $mapperMetadata->source && UserDTO::class === $mapperMetadata->target && 'yearOfBirth' === $source->name;
    }
}

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
