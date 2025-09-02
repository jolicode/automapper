<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\AutoMapper;
use AutoMapper\Configuration;
use AutoMapper\ConstructorStrategy;
use AutoMapper\Symfony\ExpressionLanguageProvider;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class AutoMapperBuilder
{
    public static function buildAutoMapper(
        bool $allowReadOnlyTargetToPopulate = false,
        bool $mapPrivatePropertiesAndMethod = false,
        ConstructorStrategy $constructorStrategy = ConstructorStrategy::AUTO,
        string $classPrefix = 'Mapper_',
        array $transformerFactories = [],
        array $propertyTransformers = [],
        array $providers = [],
        string $dateTimeFormat = \DateTimeInterface::RFC3339,
        ?ExpressionLanguageProvider $expressionLanguageProvider = null,
        EventDispatcherInterface $eventDispatcher = new EventDispatcher(),
        bool $removeDefaultProperties = false,
        ?ObjectManager $objectManager = null,
    ): AutoMapper {
        $skipCacheRemove = $_SERVER['SKIP_CACHE_REMOVE'] ?? false;

        if (!$skipCacheRemove) {
            $fs = new Filesystem();
            $fs->remove(__DIR__ . '/cache/');
        }

        $configuration = new Configuration(
            classPrefix: $classPrefix,
            constructorStrategy: $constructorStrategy,
            dateTimeFormat: $dateTimeFormat,
            mapPrivateProperties: $mapPrivatePropertiesAndMethod,
            allowReadOnlyTargetToPopulate: $allowReadOnlyTargetToPopulate,
        );

        return AutoMapper::create(
            $configuration,
            cacheDirectory: __DIR__ . '/cache/',
            transformerFactories: $transformerFactories,
            propertyTransformers: $propertyTransformers,
            expressionLanguageProvider: $expressionLanguageProvider,
            eventDispatcher: $eventDispatcher,
            providers: $providers,
            removeDefaultProperties: $removeDefaultProperties,
            objectManager: $objectManager,
        );
    }
}
