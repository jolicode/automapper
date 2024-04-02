<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\AutoMapperInterface;
use AutoMapper\Normalizer\AutoMapperNormalizer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(AutoMapperNormalizer::class)
            ->args([service(AutoMapperInterface::class)])
    ;
};
