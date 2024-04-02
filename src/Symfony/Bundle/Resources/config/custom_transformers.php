<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerFactory;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerRegistry;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(PropertyTransformerRegistry::class)
            ->args([new TaggedIteratorArgument('automapper.property_transformer', needsIndexes: true)])

        ->set(PropertyTransformerFactory::class)
            ->args([service(PropertyTransformerRegistry::class)])
            ->tag('automapper.transformer_factory', ['priority' => 1003])
    ;
};
