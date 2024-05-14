<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Configuration;
use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Generator\Shared\ClassDiscriminatorResolver;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(MapperGenerator::class)
            ->args([
                service(ClassDiscriminatorResolver::class),
                service(Configuration::class),
                service('automapper.expression_language'),
            ])

        ->set(ClassDiscriminatorResolver::class)
            ->args([null])
    ;
};
