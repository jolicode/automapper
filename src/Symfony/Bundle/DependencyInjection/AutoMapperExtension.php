<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\DependencyInjection;

use AutoMapper\Configuration as AutoMapperConfiguration;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\EventListener\Symfony\AdvancedNameConverterListener;
use AutoMapper\Loader\FileLoader;
use AutoMapper\Symfony\Bundle\CacheWarmup\CacheWarmerLoaderInterface;
use AutoMapper\Symfony\Bundle\CacheWarmup\ConfigurationCacheWarmerLoader;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\SymfonyUidTransformerFactory;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\AbstractUid;

class AutoMapperExtension extends Extension
{
    /**
     * @param array<mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new Configuration();
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var Configuration $configuration */
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../config'));

        $loader->load('automapper.php');
        $loader->load('custom_transformers.php');
        $loader->load('event.php');
        $loader->load('generator.php');
        $loader->load('metadata.php');
        $loader->load('symfony.php');
        $loader->load('transformers.php');

        $container->getDefinition(AutoMapperConfiguration::class)
            ->setArgument('$classPrefix', $config['class_prefix'])
            ->setArgument('$allowConstructor', $config['allow_constructor'])
            ->setArgument('$dateTimeFormat', $config['date_time_format'])
            ->setArgument('$attributeChecking', $config['check_attributes'])
            ->setArgument('$autoRegister', $config['auto_register'])
            ->setArgument('$mapPrivateProperties', $config['map_private_properties'])
            ->setArgument('$allowReadOnlyTargetToPopulate', $config['allow_readonly_target_to_populate'])
        ;

        $container->getDefinition(FileLoader::class)->replaceArgument(3, $config['hot_reload']);
        $container->registerForAutoconfiguration(PropertyTransformerInterface::class)->addTag('automapper.property_transformer');

        if (class_exists(AbstractUid::class)) {
            $container
                ->getDefinition(SymfonyUidTransformerFactory::class)
                ->addTag('automapper.transformer_factory', ['priority' => '-1004']);
        }

        if ($config['serializer']) {
            if (!interface_exists(SerializerInterface::class)) {
                throw new \LogicException('The "symfony/serializer" component is required to use the "serializer" feature.');
            }

            $loader->load('event_serializer.php');
        }

        if ($config['normalizer']) {
            if (!interface_exists(NormalizerInterface::class)) {
                throw new \LogicException('The "symfony/serializer" component is required to use the "normalizer" feature.');
            }

            $loader->load('normalizer.php');
        }

        if (null !== $config['name_converter']) {
            $container
                ->getDefinition(AdvancedNameConverterListener::class)
                ->replaceArgument(0, new Reference($config['name_converter']))
                ->addTag('kernel.event_listener', ['event' => PropertyMetadataEvent::class, 'priority' => -64]);
        }

        $container->setParameter('automapper.cache_dir', $config['cache_dir']);

        $container->registerForAutoconfiguration(CacheWarmerLoaderInterface::class)->addTag('automapper.cache_warmer_loader');
        $container
            ->getDefinition(ConfigurationCacheWarmerLoader::class)
            ->replaceArgument(0, $config['warmup']);
    }

    public function getAlias(): string
    {
        return 'automapper';
    }
}
