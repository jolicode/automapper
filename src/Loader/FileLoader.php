<?php

declare(strict_types=1);

namespace AutoMapper\Loader;

use AutoMapper\Exception\CompileException;
use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\MetadataFactory;
use AutoMapper\Metadata\MetadataRegistry;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Symfony\Component\Lock\LockFactory;

/**
 * Use file system to load mapper, and persist them using a registry.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class FileLoader implements ClassLoaderInterface
{
    private readonly PrettyPrinterAbstract $printer;

    /** @var array<class-string, string> */
    private array $registry;

    public function __construct(
        private readonly MapperGenerator $generator,
        private readonly MetadataFactory $metadataFactory,
        private readonly string $directory,
        private readonly LockFactory $lockFactory,
        private readonly FileReloadStrategy $reloadStrategy = FileReloadStrategy::ON_CHANGE,
    ) {
        $this->printer = new Standard();
    }

    public function loadClass(MapperMetadata $mapperMetadata): void
    {
        $className = $mapperMetadata->className;

        $classPath = $this->directory . \DIRECTORY_SEPARATOR . $className . '.php';

        // We lock the file here, because another process could be writing the file at the same time
        $lock = $this->lockFactory->createLock($className);
        $lock->acquire(true);

        try {
            if ($this->reloadStrategy === FileReloadStrategy::NEVER && file_exists($classPath)) {
                require $classPath;

                return;
            }

            $shouldBuildMapper = true;

            if ($this->reloadStrategy === FileReloadStrategy::ON_CHANGE) {
                $registry = $this->getRegistry();
                $hash = $mapperMetadata->getHash();
                $shouldBuildMapper = !isset($registry[$className]) || $registry[$className] !== $hash || !file_exists($classPath);
            }

            if ($shouldBuildMapper) {
                $this->createGeneratedMapper($mapperMetadata);
            }

            require $classPath;
        } finally {
            $lock->release();
        }
    }

    public function buildMappers(MetadataRegistry $registry): bool
    {
        foreach ($registry as $metadata) {
            $this->createGeneratedMapper($metadata);
        }

        return true;
    }

    /**
     * @return string The generated class name
     */
    public function createGeneratedMapper(MapperMetadata $mapperMetadata): string
    {
        $className = $mapperMetadata->className;
        $classPath = $this->directory . \DIRECTORY_SEPARATOR . $className . '.php';

        $classCode = $this->printer->prettyPrint($this->generator->generate(
            $this->metadataFactory->getGeneratorMetadata($mapperMetadata->source, $mapperMetadata->target)
        ));

        $this->write($classPath, "<?php\n\n" . $classCode . "\n");

        if ($this->reloadStrategy === FileReloadStrategy::ON_CHANGE) {
            $this->addHashToRegistry($className, $mapperMetadata->getHash());
        }

        return $className;
    }

    /**
     * @param class-string<object> $className
     */
    private function addHashToRegistry(string $className, string $hash): void
    {
        $registryPath = $this->directory . \DIRECTORY_SEPARATOR . 'registry.php';

        // The registry is shared by all mappers: it is locked globally and reloaded from disk so entries
        // written concurrently by other processes are not lost
        $lock = $this->lockFactory->createLock('automapper_registry');
        $lock->acquire(true);

        try {
            $this->registry = $this->readRegistry();
            $this->registry[$className] = $hash;
            $this->write($registryPath, "<?php\n\nreturn " . var_export($this->registry, true) . ";\n");
        } finally {
            $lock->release();
        }
    }

    /** @return array<class-string, string> */
    private function getRegistry(): array
    {
        return $this->registry ??= $this->readRegistry();
    }

    /** @return array<class-string, string> */
    private function readRegistry(): array
    {
        $registryPath = $this->directory . \DIRECTORY_SEPARATOR . 'registry.php';

        if (!file_exists($registryPath)) {
            return [];
        }

        $registry = require $registryPath;

        if (!\is_array($registry)) {
            // corrupted registry, mappers will be regenerated
            return [];
        }

        /** @var array<class-string, string> $registry */
        return $registry;
    }

    private function write(string $file, string $contents): void
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new CompileException(\sprintf('Could not create directory "%s"', $this->directory));
        }

        // Write to a temporary file then rename: the rename is atomic so a concurrent process can never
        // require a partially written file
        $tmpFile = tempnam($this->directory, 'am_');

        if (false === $tmpFile || false === file_put_contents($tmpFile, $contents)) {
            throw new CompileException(\sprintf('Could not write file "%s"', $file));
        }

        @chmod($tmpFile, 0666 & ~umask());

        if (!rename($tmpFile, $file)) {
            @unlink($tmpFile);

            throw new CompileException(\sprintf('Could not write file "%s"', $file));
        }

        if (\function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }
    }
}
