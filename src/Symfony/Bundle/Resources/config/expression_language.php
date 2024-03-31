<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Symfony\ExpressionLanguageProvider;
use Symfony\Component\DependencyInjection\ExpressionLanguage;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('automapper.expression_language', ExpressionLanguage::class)
            ->call('registerProvider', [
                service(ExpressionLanguageProvider::class),
            ])

        ->set(ExpressionLanguageProvider::class)
            ->args([
                tagged_locator('automapper.expression_language_function', 'function'),
            ])
            ->tag('automapper.expression_language_provider')

        ->set('automapper.expression_language.env', \Closure::class)
            ->factory([\Closure::class, 'fromCallable'])
            ->args([
                [service('service_container'), 'getEnv'],
            ])
            ->tag('automapper.expression_language_function', ['function' => 'env'])

        ->set('automapper.expression_language.service', \Closure::class)
            ->public()
            ->factory([\Closure::class, 'fromCallable'])
            ->args([
                [tagged_locator('automapper.expression_service', 'alias'), 'get'],
            ])
            ->tag('automapper.expression_language_function', ['function' => 'service'])
    ;
};
