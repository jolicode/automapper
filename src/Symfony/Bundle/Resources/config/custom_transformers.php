<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerFactory;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ServiceLocator;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('automapper.mapper_service_locator')
            ->class(ServiceLocator::class)
            ->args([[]])

        ->set(PropertyTransformerFactory::class)
            ->args([
                new TaggedIteratorArgument('automapper.property_transformer_support', needsIndexes: true)
            ])
            ->tag('automapper.transformer_factory', ['priority' => 1003])
    ;
};
