<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Loader;

use AutoMapper\AutoMapper;
use AutoMapper\Configuration;
use AutoMapper\Loader\FileLoader;
use AutoMapper\Metadata\MetadataRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

// each test uses its own class: a generated mapper class stays loaded for the whole
// php process, so reusing a class between tests would skip the generation under test
class FirstDummy
{
    public string $foo = 'foo';
}

class SecondDummy
{
    public string $bar = 'bar';
}

class NestedDirectoryDummy
{
    public string $foo = 'foo';
}

class CorruptedRegistryDummy
{
    public string $foo = 'foo';
}

class TemporaryFileDummy
{
    public string $foo = 'foo';
}

class FileLoaderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . uniqid('automapper_file_loader_', true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    public function testRegistryKeepsEntriesWrittenByOtherProcesses(): void
    {
        // a first process generates a mapper, its hash is stored in the registry
        $autoMapper = AutoMapper::create(cacheDirectory: $this->directory);
        $autoMapper->map(new FirstDummy(), 'array');

        $registry = require $this->directory . \DIRECTORY_SEPARATOR . 'registry.php';
        self::assertIsArray($registry);
        self::assertCount(1, $registry);

        // a fresh instance (e.g. the cache warmer) generates another mapper without having read the registry first
        $freshAutoMapper = AutoMapper::create(cacheDirectory: $this->directory);
        $loader = (new \ReflectionProperty(AutoMapper::class, 'classLoader'))->getValue($freshAutoMapper);
        self::assertInstanceOf(FileLoader::class, $loader);

        $metadataRegistry = new MetadataRegistry(new Configuration());
        $metadataRegistry->register(SecondDummy::class, 'array');
        $loader->buildMappers($metadataRegistry);

        // both hashes must be present, the first entry must not have been discarded
        $registry = require $this->directory . \DIRECTORY_SEPARATOR . 'registry.php';
        self::assertIsArray($registry);
        self::assertCount(2, $registry);
    }

    public function testCacheDirectoryIsCreatedRecursively(): void
    {
        $nestedDirectory = $this->directory . \DIRECTORY_SEPARATOR . 'nested' . \DIRECTORY_SEPARATOR . 'deep';

        $autoMapper = AutoMapper::create(cacheDirectory: $nestedDirectory);
        $mapped = $autoMapper->map(new NestedDirectoryDummy(), 'array');

        self::assertSame(['foo' => 'foo'], $mapped);
        self::assertDirectoryExists($nestedDirectory);
    }

    public function testCorruptedRegistryIsIgnored(): void
    {
        mkdir($this->directory, 0755, true);
        file_put_contents($this->directory . \DIRECTORY_SEPARATOR . 'registry.php', "<?php\n\nreturn 1;\n");

        $autoMapper = AutoMapper::create(cacheDirectory: $this->directory);
        $mapped = $autoMapper->map(new CorruptedRegistryDummy(), 'array');

        self::assertSame(['foo' => 'foo'], $mapped);

        // the registry has been rebuilt
        $registry = require $this->directory . \DIRECTORY_SEPARATOR . 'registry.php';
        self::assertIsArray($registry);
        self::assertCount(1, $registry);
    }

    public function testNoTemporaryFileIsLeftBehind(): void
    {
        $autoMapper = AutoMapper::create(cacheDirectory: $this->directory);
        $autoMapper->map(new TemporaryFileDummy(), 'array');

        foreach (new \DirectoryIterator($this->directory) as $file) {
            if ($file->isDot()) {
                continue;
            }

            self::assertStringEndsWith('.php', $file->getFilename(), 'Only fully written php files should be present in the cache directory.');
        }
    }
}
