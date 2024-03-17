<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Transformer\CustomTransformer\CustomTransformerFactory;
use AutoMapper\Transformer\CustomTransformer\CustomTransformersRegistry;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(CustomTransformersRegistry::class)

        ->set(CustomTransformerFactory::class)
            ->args([service(CustomTransformersRegistry::class)])
            ->tag('automapper.transformer_factory', ['priority' => 1003])
    ;
};
