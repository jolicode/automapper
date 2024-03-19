<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\AutoMapper;
use AutoMapper\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
abstract class AutoMapperBaseTest extends TestCase
{
    protected AutoMapper $autoMapper;

    protected function setUp(): void
    {
        unset($this->autoMapper);
        $this->buildAutoMapper();
    }

    protected function buildAutoMapper(
        bool $allowReadOnlyTargetToPopulate = false,
        bool $mapPrivatePropertiesAndMethod = false,
        bool $allowConstructor = true,
        string $classPrefix = 'Mapper_',
        array $transformerFactories = [],
        array $propertyTransformers = [],
        string $dateTimeFormat = \DateTimeInterface::RFC3339
    ): AutoMapper {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/cache/');

        $configuration = new Configuration(
            classPrefix: $classPrefix,
            allowConstructor: $allowConstructor,
            dateTimeFormat: $dateTimeFormat,
            mapPrivateProperties: $mapPrivatePropertiesAndMethod,
            allowReadOnlyTargetToPopulate: $allowReadOnlyTargetToPopulate
        );

        return $this->autoMapper = AutoMapper::create($configuration, cacheDirectory: __DIR__ . '/cache/', transformerFactories: $transformerFactories, propertyTransformers: $propertyTransformers);
    }
}
