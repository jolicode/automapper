<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\DependencyInjection;

use AutoMapper\ConstructorStrategy;
use AutoMapper\Loader\FileReloadStrategy;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Serializer\SerializerInterface;

readonly class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder<'array'>
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('automapper');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('class_prefix')->defaultValue('Symfony_Mapper_')->end()
                ->enumNode('constructor_strategy')
                    ->values(array_map(fn (ConstructorStrategy $strategy) => $strategy->value, ConstructorStrategy::cases()))
                    ->defaultValue(ConstructorStrategy::AUTO->value)
                ->end()
                ->scalarNode('date_time_format')->defaultValue(\DateTimeInterface::RFC3339)->end()
                ->booleanNode('check_attributes')->defaultTrue()->end()
                ->booleanNode('auto_register')->defaultTrue()->end()
                ->booleanNode('map_private_properties')->defaultTrue()->end()
                ->booleanNode('allow_readonly_target_to_populate')->defaultFalse()->end()
                ->arrayNode('normalizer')
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->booleanNode('only_registered_mapping')->defaultFalse()->end()
                        ->integerNode('priority')->defaultValue(1000)->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                ->end()
                ->booleanNode('serializer_attributes')->defaultValue(interface_exists(SerializerInterface::class))->end()
                ->booleanNode('api_platform')->defaultFalse()->end()
                ->booleanNode('doctrine')->defaultFalse()->end()
                ->scalarNode('name_converter')->defaultNull()->end()
                ->arrayNode('loader')
                    ->children()
                        ->booleanNode('eval')->defaultFalse()->end()
                        ->scalarNode('cache_dir')->defaultValue('%kernel.cache_dir%/automapper')->end()
                        ->enumNode('reload_strategy')->values(array_map(fn (FileReloadStrategy $value) => $value->value, FileReloadStrategy::cases()))->defaultNull()->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                ->end()
                ->scalarNode('date_time_format')->defaultValue(\DateTimeInterface::RFC3339)->end()
                ->booleanNode('map_private_properties')->defaultFalse()->end()
                ->arrayNode('mapping')
                    ->children()
                        ->arrayNode('paths')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('mappers')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('source')->defaultValue('array')->end()
                                    ->scalarNode('target')->defaultValue('array')->end()
                                    ->booleanNode('reverse')->defaultFalse()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
