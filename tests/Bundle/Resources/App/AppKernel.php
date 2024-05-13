<?php

declare(strict_types=1);

namespace DummyApp;

use AutoMapper\Symfony\Bundle\AutoMapperBundle;
use AutoMapper\Tests\Bundle\Fixtures\User;
use AutoMapper\Tests\Bundle\Fixtures\UserDTO;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformerInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(
        string $environment,
        bool $debug,
        private ?string $additionalConfigFile = null
    ) {
        parent::__construct($environment, $debug);
    }

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

        if ($this->additionalConfigFile) {
            $loader->load($this->additionalConfigFile);
        }
    }

    public function getProjectDir(): string
    {
        return __DIR__ . '/..';
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir() . '/' . spl_object_hash($this);
    }
}

class YearOfBirthTransformer implements CustomPropertyTransformerInterface
{
    public function transform(object|array $user): mixed
    {
        \assert($user instanceof User);

        return ((int) date('Y')) - ((int) $user->age);
    }

    public function supports(string $source, string $target, string $sourceProperty, string $targetProperty): bool
    {
        return User::class === $source && UserDTO::class === $target && 'yearOfBirth' === $sourceProperty;
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
