<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\DependencyInjection;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\EventListener\Doctrine\DoctrineProviderListener;
use AutoMapper\EventListener\ObjectMapper\MapSourceListener;
use AutoMapper\EventListener\ObjectMapper\MapTargetListener;
use AutoMapper\EventListener\Symfony\ClassDiscriminatorListener;
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

    public function testObjectMapperListenersRunAfterDoctrineAndDiscriminator(): void
    {
        $this->container->setParameter('kernel.debug', false);
        $this->load([
            'object_mapper' => true,
            'doctrine' => true,
            'serializer_attributes' => true,
        ]);

        $mapSource = $this->generateMapperListenerPriority(MapSourceListener::class);
        $mapTarget = $this->generateMapperListenerPriority(MapTargetListener::class);
        $doctrine = $this->generateMapperListenerPriority(DoctrineProviderListener::class);
        $discriminator = $this->generateMapperListenerPriority(ClassDiscriminatorListener::class);

        // higher priority runs first: MapSource/MapTarget must run after the Doctrine and discriminator
        // listeners so their stopPropagation for Map-attributed classes does not suppress them
        self::assertLessThan($doctrine, $mapSource);
        self::assertLessThan($doctrine, $mapTarget);
        self::assertLessThan($discriminator, $mapSource);
        self::assertLessThan($discriminator, $mapTarget);
    }

    public function testJsonStreamerIsNotRegisteredByDefault(): void
    {
        $this->container->setParameter('kernel.debug', false);
        $this->load();

        $this->assertContainerBuilderNotHasService('automapper.json_streamer.stream_reader');
        $this->assertContainerBuilderNotHasService('automapper.json_streamer.stream_writer');
    }

    public function testJsonStreamerDecoratesTheSymfonyServices(): void
    {
        $this->container->setParameter('kernel.debug', false);
        $this->load(['json_streamer' => ['enabled' => true]]);

        foreach (['reader', 'writer'] as $kind) {
            $definition = $this->container->getDefinition("automapper.json_streamer.stream_{$kind}");

            self::assertSame("json_streamer.stream_{$kind}", $definition->getDecoratedService()[0]);
            // the decorated Symfony service is kept as the fallback
            self::assertSame(
                "automapper.json_streamer.stream_{$kind}.inner",
                (string) $definition->getArgument(1),
            );
            // registry awareness is opt-in
            self::assertArrayNotHasKey('$onlyMetadataRegistry', $definition->getArguments());
        }
    }

    public function testJsonStreamerOnlyRegisteredMappingUsesTheConfigRegistry(): void
    {
        $this->container->setParameter('kernel.debug', false);
        $this->load(['json_streamer' => ['enabled' => true, 'only_registered_mapping' => true]]);

        foreach (['reader', 'writer'] as $kind) {
            $definition = $this->container->getDefinition("automapper.json_streamer.stream_{$kind}");

            self::assertSame(
                'automapper.config_mapping_registry',
                (string) $definition->getArgument('$onlyMetadataRegistry'),
            );
        }
    }

    private function generateMapperListenerPriority(string $serviceId): int
    {
        $definition = $this->container->getDefinition($serviceId);

        foreach ($definition->getTag('kernel.event_listener') as $tag) {
            if (($tag['event'] ?? null) === GenerateMapperEvent::class) {
                return (int) ($tag['priority'] ?? 0);
            }
        }

        self::fail(\sprintf('Service "%s" has no GenerateMapperEvent listener tag.', $serviceId));
    }

    protected function getContainerExtensions(): array
    {
        return [new AutoMapperExtension()];
    }
}
