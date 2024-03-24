<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Provider\ProviderRegistry;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(ProviderRegistry::class)
            ->args([new TaggedIteratorArgument('automapper.provider', needsIndexes: true)])
    ;
};
