<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Serializer\SerializerInterface;

readonly class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('automapper');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        /* @phpstan-ignore-next-line */
        $rootNode
            ->children()
                ->scalarNode('class_prefix')->defaultValue('Symfony_Mapper_')->end()
                ->booleanNode('eval')->defaultFalse()->end()
                ->booleanNode('allow_constructor')->defaultTrue()->end()
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
                ->booleanNode('serializer')->defaultValue(interface_exists(SerializerInterface::class))->end()
                ->scalarNode('name_converter')->defaultNull()->end()
                ->scalarNode('cache_dir')->defaultValue('%kernel.cache_dir%/automapper')->end()
                ->scalarNode('date_time_format')->defaultValue(\DateTimeInterface::RFC3339)->end()
                ->booleanNode('hot_reload')->defaultValue('%kernel.debug%')->end()
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
