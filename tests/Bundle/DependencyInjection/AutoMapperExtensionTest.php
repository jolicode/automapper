<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\DependencyInjection;

use AutoMapper\Loader\FileLoader;
use AutoMapper\Loader\FileReloadStrategy;
use AutoMapper\Symfony\Bundle\CacheWarmup\CacheWarmer;
use AutoMapper\Symfony\Bundle\DependencyInjection\AutoMapperExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class AutoMapperExtensionTest extends AbstractExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->container->setParameter('kernel.environment', 'prod');
    }

    #[DataProvider('provideReloadStrategyConfiguration')]
    public function testItCorrectlyConfiguresReloadStrategy(array $config, bool $debug, FileReloadStrategy $expectedValue): void
    {
        $this->container->setParameter('kernel.debug', $debug);
        $this->load(['loader' => $config]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(FileLoader::class, 4, $expectedValue);
    }

    public static function provideReloadStrategyConfiguration(): iterable
    {
        yield 'Never reload if no conf and no debug' => [[], false, FileReloadStrategy::NEVER];
        yield 'Always reload if no conf and no debug' => [[], true, FileReloadStrategy::ALWAYS];
        yield 'Applies configured reload strategy if provided' => [['reload_strategy' => FileReloadStrategy::NEVER->value], true, FileReloadStrategy::NEVER];
    }

    public function testEvalLoaderDoesNotRegisterTheCacheWarmer(): void
    {
        $this->container->setParameter('kernel.debug', false);
        $this->load(['loader' => ['eval' => true]]);

        // there is no cache directory with the eval loader: the cache warmer must not be registered,
        // otherwise the container fails to compile on the missing automapper.cache_dir parameter
        $this->assertContainerBuilderNotHasService(CacheWarmer::class);

        $this->container->compile();
    }

    public function testFileLoaderRegistersTheCacheWarmer(): void
    {
        $this->container->setParameter('kernel.debug', false);
        $this->load();

        $this->assertContainerBuilderHasService(CacheWarmer::class);
        $this->assertContainerBuilderHasParameter('automapper.cache_dir');
    }

    protected function getContainerExtensions(): array
    {
        return [new AutoMapperExtension()];
    }
}
