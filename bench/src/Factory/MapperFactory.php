<?php

declare(strict_types=1);

namespace Automapper\Bench\Factory;

use AutoMapper\AutoMapper;
use AutoMapper\AutoMapperInterface;
use AutoMapper\Configuration;
use AutoMapper\ConstructorStrategy;
use AutoMapper\JsonStreamer\JsonStreamReader as AutoMapperJsonStreamReader;
use AutoMapper\JsonStreamer\JsonStreamWriter as AutoMapperJsonStreamWriter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\JsonStreamer\JsonStreamReader;
use Symfony\Component\JsonStreamer\JsonStreamWriter;
use Symfony\Component\ObjectMapper\Metadata\ReflectionObjectMapperMetadataFactory;
use Symfony\Component\ObjectMapper\ObjectMapper as SymfonyObjectMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\CacheClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Builds and memoizes every library under test.
 *
 * Construction (and, for AutoMapper, mapper code generation) is expensive and
 * must never land inside a measured loop, so each getter caches its result and
 * the benchmarks build everything once from their constructor.
 */
final class MapperFactory
{
    /** @var array<string, AutoMapperInterface> */
    private static array $autoMappers = [];

    private static ?Serializer $serializer = null;
    private static ?JsonStreamReader $jsonStreamReader = null;
    private static ?JsonStreamWriter $jsonStreamWriter = null;
    private static ?AutoMapperJsonStreamReader $autoMapperJsonStreamReader = null;
    private static ?AutoMapperJsonStreamWriter $autoMapperJsonStreamWriter = null;
    private static ?AutoMapperJsonStreamReader $autoMapperNoAttributeJsonStreamReader = null;
    private static ?AutoMapperJsonStreamWriter $autoMapperNoAttributeJsonStreamWriter = null;
    private static ?SymfonyObjectMapper $symfonyObjectMapper = null;
    private static ?\AutoMapper\ObjectMapper\ObjectMapper $autoMapperObjectMapper = null;

    /**
     * The default AutoMapper: file cache loader, constructor strategy AUTO.
     * This is what an application gets out of the box.
     */
    public static function autoMapper(): AutoMapperInterface
    {
        return self::$autoMappers['default'] ??= AutoMapper::create(
            new Configuration(classPrefix: 'BenchDefault_'),
            cacheDirectory: self::cacheDir('default'),
        );
    }

    /**
     * AutoMapper with the EvalLoader (no on-disk cache): mappers are generated
     * and eval()'d into the current process instead of written to files.
     */
    public static function autoMapperEval(): AutoMapperInterface
    {
        return self::$autoMappers['eval'] ??= AutoMapper::create(
            new Configuration(classPrefix: 'BenchEval_'),
        );
    }

    /**
     * AutoMapper that never uses the target constructor (writes properties directly).
     */
    public static function autoMapperNoConstructor(): AutoMapperInterface
    {
        return self::$autoMappers['no_constructor'] ??= AutoMapper::create(
            new Configuration(
                classPrefix: 'BenchNoCtor_',
                constructorStrategy: ConstructorStrategy::NEVER,
            ),
            cacheDirectory: self::cacheDir('no_constructor'),
        );
    }

    /**
     * AutoMapper with attribute checking disabled (skips reading Serializer/AutoMapper
     * attributes when building metadata — cheaper generation, same runtime shape here).
     */
    public static function autoMapperNoAttributeChecking(): AutoMapperInterface
    {
        return self::$autoMappers['no_attribute'] ??= AutoMapper::create(
            new Configuration(
                classPrefix: 'BenchNoAttr_',
                attributeChecking: false,
                mapPrivateProperties: false,
            ),
            cacheDirectory: self::cacheDir('no_attribute'),
        );
    }

    /**
     * A Serializer wired like Symfony FrameworkBundle's default service: the
     * property-info extractor and the class-metadata factory are both wrapped in a
     * cache, and the ObjectNormalizer uses a cached PropertyAccessor. Without these
     * caches, every (de)normalization re-parses the PHPDoc types (via
     * phpstan/phpdoc-parser), which is not what a real application pays after warmup.
     */
    public static function serializer(): Serializer
    {
        if (null !== self::$serializer) {
            return self::$serializer;
        }

        $reflectionExtractor = new ReflectionExtractor();
        $phpStanExtractor = new PhpStanExtractor();

        $propertyInfo = new PropertyInfoCacheExtractor(
            new PropertyInfoExtractor(
                listExtractors: [$reflectionExtractor],
                typeExtractors: [$phpStanExtractor, $reflectionExtractor],
                descriptionExtractors: [],
                accessExtractors: [$reflectionExtractor],
                initializableExtractors: [$reflectionExtractor],
            ),
            new ArrayAdapter(),
        );

        $classMetadataFactory = new CacheClassMetadataFactory(
            new ClassMetadataFactory(new AttributeLoader()),
            new ArrayAdapter(),
        );

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->setCacheItemPool(new ArrayAdapter())
            ->getPropertyAccessor();

        $normalizer = new ObjectNormalizer(
            classMetadataFactory: $classMetadataFactory,
            propertyAccessor: $propertyAccessor,
            propertyTypeExtractor: $propertyInfo,
        );

        return self::$serializer = new Serializer(
            [$normalizer, new ArrayDenormalizer()],
            [new JsonEncoder()],
        );
    }

    public static function jsonStreamReader(): JsonStreamReader
    {
        return self::$jsonStreamReader ??= JsonStreamReader::create(
            streamReadersDir: self::tmpDir('sf-json-streamer/read'),
        );
    }

    public static function jsonStreamWriter(): JsonStreamWriter
    {
        return self::$jsonStreamWriter ??= JsonStreamWriter::create(
            streamWritersDir: self::tmpDir('sf-json-streamer/write'),
        );
    }

    public static function autoMapperJsonStreamReader(): AutoMapperJsonStreamReader
    {
        return self::$autoMapperJsonStreamReader ??= new AutoMapperJsonStreamReader(
            self::autoMapper(),
            self::jsonStreamReader(),
        );
    }

    public static function autoMapperJsonStreamWriter(): AutoMapperJsonStreamWriter
    {
        return self::$autoMapperJsonStreamWriter ??= new AutoMapperJsonStreamWriter(
            self::autoMapper(),
            self::jsonStreamWriter(),
        );
    }

    public static function autoMapperNoAttributeJsonStreamReader(): AutoMapperJsonStreamReader
    {
        return self::$autoMapperNoAttributeJsonStreamReader ??= new AutoMapperJsonStreamReader(
            self::autoMapperNoAttributeChecking(),
            self::jsonStreamReader(),
        );
    }

    public static function autoMapperNoAttributeJsonStreamWriter(): AutoMapperJsonStreamWriter
    {
        return self::$autoMapperNoAttributeJsonStreamWriter ??= new AutoMapperJsonStreamWriter(
            self::autoMapperNoAttributeChecking(),
            self::jsonStreamWriter(),
        );
    }

    /**
     * ObjectMapper wired like Symfony FrameworkBundle's default service: the
     * reflection metadata factory (which already caches internally) plus a cached
     * PropertyAccessor, as the `object_mapper` service is configured.
     */
    public static function symfonyObjectMapper(): SymfonyObjectMapper
    {
        return self::$symfonyObjectMapper ??= new SymfonyObjectMapper(
            new ReflectionObjectMapperMetadataFactory(),
            PropertyAccess::createPropertyAccessorBuilder()
                ->setCacheItemPool(new ArrayAdapter())
                ->getPropertyAccessor(),
        );
    }

    public static function autoMapperObjectMapper(): \AutoMapper\ObjectMapper\ObjectMapper
    {
        return self::$autoMapperObjectMapper ??= new \AutoMapper\ObjectMapper\ObjectMapper(self::autoMapper());
    }

    private static function cacheDir(string $variant): string
    {
        return self::tmpDir('automapper-cache/' . $variant);
    }

    private static function tmpDir(string $suffix): string
    {
        $dir = sys_get_temp_dir() . '/automapper-bench/' . $suffix;

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir;
    }
}
