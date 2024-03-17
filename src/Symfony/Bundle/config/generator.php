<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Configuration;
use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Generator\Shared\ClassDiscriminatorResolver;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(MapperGenerator::class)
            ->args([
                service(ClassDiscriminatorResolver::class),
                service(Configuration::class),
            ])

        ->set(ClassDiscriminatorResolver::class)
            ->args([service('automapper.mapping.class_discriminator_from_class_metadata')])

        ->set('automapper.mapping.class_discriminator_from_class_metadata', ClassDiscriminatorFromClassMetadata::class)
            ->args([service('serializer.mapping.class_metadata_factory')])
    ;
};
