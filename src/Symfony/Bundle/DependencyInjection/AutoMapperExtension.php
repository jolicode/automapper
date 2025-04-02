<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\DependencyInjection;

use AutoMapper\Attribute\Mapper;
use AutoMapper\Configuration as AutoMapperConfiguration;
use AutoMapper\ConstructorStrategy;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\EventListener\Symfony\AdvancedNameConverterListener;
use AutoMapper\Exception\LogicException;
use AutoMapper\Loader\ClassLoaderInterface;
use AutoMapper\Loader\EvalLoader;
use AutoMapper\Loader\FileLoader;
use AutoMapper\Loader\FileReloadStrategy;
use AutoMapper\Normalizer\AutoMapperNormalizer;
use AutoMapper\Provider\ProviderInterface;
use AutoMapper\Symfony\Bundle\CacheWarmup\CacheWarmer;
use AutoMapper\Symfony\Bundle\ReflectionClassRecursiveIterator;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\SymfonyUidTransformerFactory;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
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

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('automapper.php');
        $loader->load('custom_transformers.php');
        $loader->load('event.php');
        $loader->load('expression_language.php');
        $loader->load('generator.php');
        $loader->load('metadata.php');
        $loader->load('property_info.php');
        $loader->load('provider.php');
        $loader->load('symfony.php');
        $loader->load('transformers.php');

        $container->getDefinition(AutoMapperConfiguration::class)
            ->setArgument('$classPrefix', $config['class_prefix'])
            ->setArgument('$constructorStrategy', ConstructorStrategy::tryFrom($config['constructor_strategy']) ?? ConstructorStrategy::AUTO)
            ->setArgument('$dateTimeFormat', $config['date_time_format'])
            ->setArgument('$attributeChecking', $config['check_attributes'])
            ->setArgument('$autoRegister', $config['auto_register'])
            ->setArgument('$mapPrivateProperties', $config['map_private_properties'])
            ->setArgument('$allowReadOnlyTargetToPopulate', $config['allow_readonly_target_to_populate'])
            ->setArgument('$reloadStrategy', $reloadStrategy = FileReloadStrategy::from($config['loader']['reload_strategy'] ?? (
                $container->getParameter('kernel.debug') ? FileReloadStrategy::ALWAYS->value : FileReloadStrategy::NEVER->value
            )))
        ;

        if ($config['map_private_properties']) {
            $container->getDefinition('automapper.property_info.reflection_extractor')
                ->replaceArgument('$accessFlags', ReflectionExtractor::ALLOW_PUBLIC | ReflectionExtractor::ALLOW_PRIVATE | ReflectionExtractor::ALLOW_PROTECTED);
        }

        $container->setParameter('automapper.map_private_properties', $config['map_private_properties']);

        $container->registerForAutoconfiguration(PropertyTransformerInterface::class)->addTag('automapper.property_transformer');

        $container->registerForAutoconfiguration(ProviderInterface::class)->addTag('automapper.provider');

        if ($config['loader']['eval']) {
            $container
                ->setAlias(ClassLoaderInterface::class, EvalLoader::class)
            ;
        } else {
            $container
                ->getDefinition(FileLoader::class)
                ->replaceArgument(4, $reloadStrategy);

            $container
                ->setAlias(ClassLoaderInterface::class, FileLoader::class)
            ;

            $container->setParameter('automapper.cache_dir', $config['loader']['cache_dir']);
        }

        if (class_exists(AbstractUid::class)) {
            $container
                ->getDefinition(SymfonyUidTransformerFactory::class)
                ->addTag('automapper.transformer_factory', ['priority' => '-1004']);
        }

        if ($config['serializer_attributes']) {
            if (!interface_exists(SerializerInterface::class)) {
                throw new LogicException('The "symfony/serializer" component is required to use the "serializer" feature.');
            }

            $loader->load('event_serializer.php');
        }

        if ($config['normalizer']['enabled']) {
            if (!interface_exists(NormalizerInterface::class)) {
                throw new LogicException('The "symfony/serializer" component is required to use the "normalizer" feature.');
            }

            $loader->load('normalizer.php');

            $normalizerDefinition = $container
                ->getDefinition(AutoMapperNormalizer::class)
                ->addTag('serializer.normalizer', ['priority' => $config['normalizer']['priority']])
            ;

            if ($config['normalizer']['only_registered_mapping']) {
                $normalizerDefinition->setArgument('$onlyMetadataRegistry', new Reference('automapper.config_mapping_registry'));
            }
        }

        if ($config['api_platform']) {
            $loader->load('api_platform.php');
        }

        if (null !== $config['name_converter']) {
            if ($container->has('automapper.mapping.metadata_aware_name_converter')) {
                $container->getDefinition('automapper.mapping.metadata_aware_name_converter')
                    ->setArgument(1, new Reference($config['name_converter']));
            } else {
                $container
                    ->getDefinition(AdvancedNameConverterListener::class)
                    ->replaceArgument(0, new Reference($config['name_converter']))
                    ->addTag('kernel.event_listener', ['event' => PropertyMetadataEvent::class, 'priority' => -64]);
            }
        }

        $configMappingRegistry = $container->getDefinition('automapper.config_mapping_registry');

        foreach ($config['mapping']['mappers'] as $mapper) {
            $configMappingRegistry->addMethodCall('register', [$mapper['source'], $mapper['target']]);

            if ($mapper['reverse']) {
                $configMappingRegistry->addMethodCall('register', [$mapper['target'], $mapper['source']]);
            }
        }

        foreach (ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories($config['mapping']['paths']) as $className => $reflectionClass) {
            $mapperAttributes = $reflectionClass->getAttributes(Mapper::class);

            foreach ($mapperAttributes as $mapperAttribute) {
                $mapper = $mapperAttribute->newInstance();

                if (null !== $mapper->source) {
                    $sources = \is_array($mapper->source) ? $mapper->source : [$mapper->source];

                    foreach ($sources as $source) {
                        $configMappingRegistry->addMethodCall('register', [$source, $className]);
                    }
                }

                if (null !== $mapper->target) {
                    $targets = \is_array($mapper->target) ? $mapper->target : [$mapper->target];

                    foreach ($targets as $target) {
                        $configMappingRegistry->addMethodCall('register', [$className, $target]);
                    }
                }
            }
        }

        if ($container->getParameter('kernel.environment') === 'test') {
            $container->getDefinition(CacheWarmer::class)->setPublic(true);
        }
    }

    public function getAlias(): string
    {
        return 'automapper';
    }
}
