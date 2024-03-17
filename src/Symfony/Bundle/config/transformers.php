<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Transformer\ArrayTransformerFactory;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\DateTimeTransformerFactory;
use AutoMapper\Transformer\EnumTransformerFactory;
use AutoMapper\Transformer\MultipleTransformerFactory;
use AutoMapper\Transformer\NullableTransformerFactory;
use AutoMapper\Transformer\ObjectTransformerFactory;
use AutoMapper\Transformer\SymfonyUidTransformerFactory;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\UniqueTypeTransformerFactory;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(ChainTransformerFactory::class)
            ->args([
                [], // transformers list from DI
            ])
            ->alias(TransformerFactoryInterface::class, ChainTransformerFactory::class)

        ->set(MultipleTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => 1002])

        ->set(NullableTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => 1001])

        ->set(UniqueTypeTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => 1000])

        ->set(EnumTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => -999])

        ->set(DateTimeTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => -1000])

        ->set(BuiltinTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => -1001])

        ->set(ArrayTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => -1002])

        ->set(ObjectTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => -1003])

        ->set(SymfonyUidTransformerFactory::class)
    ;
};
