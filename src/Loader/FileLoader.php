<?php

declare(strict_types=1);

namespace AutoMapper\Loader;

use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\MetadataRegistry;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;

/**
 * Use file system to load mapper, and persist them using a registry.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 *  @internal
 */
final class FileLoader implements ClassLoaderInterface
{
    private readonly PrettyPrinterAbstract $printer;

    /** @var array<class-string, string>|null */
    private ?array $registry = null;

    public function __construct(
        private readonly MapperGenerator $generator,
        private readonly MetadataRegistry $metadataRegistry,
        private readonly string $directory,
        private readonly bool $hotReload = true,
    ) {
        $this->printer = new Standard();
    }

    public function loadClass(MapperMetadata $mapperMetadata): void
    {
        $className = $mapperMetadata->className;
        $classPath = $this->directory . \DIRECTORY_SEPARATOR . $className . '.php';

        if (!$this->hotReload && file_exists($classPath)) {
            require $classPath;

            return;
        }

        $shouldSaveMapper = true;
        if ($this->hotReload) {
            $registry = $this->getRegistry();
            $hash = $mapperMetadata->getHash();
            $shouldSaveMapper = !isset($registry[$className]) || $registry[$className] !== $hash || !file_exists($classPath);
        }

        if ($shouldSaveMapper) {
            $this->saveMapper($mapperMetadata);
        }

        require $classPath;
    }

    /**
     * @return string The generated class name
     */
    public function saveMapper(MapperMetadata $mapperMetadata): string
    {
        $className = $mapperMetadata->className;
        $classPath = $this->directory . \DIRECTORY_SEPARATOR . $className . '.php';

        $generatorMetadata = $this->metadataRegistry->getGeneratorMetadata($mapperMetadata->source, $mapperMetadata->target);
        $classCode = $this->printer->prettyPrint([$this->generator->generate($generatorMetadata)]);

        $this->write($classPath, "<?php\n\n" . $classCode . "\n");
        if ($this->hotReload) {
            $this->addHashToRegistry($className, $mapperMetadata->getHash());
        }

        return $className;
    }

    /**
     * @param class-string<object> $className
     */
    private function addHashToRegistry(string $className, string $hash): void
    {
        if (null === $this->registry) {
            $this->registry = [];
        }

        $registryPath = $this->directory . \DIRECTORY_SEPARATOR . 'registry.php';
        $this->registry[$className] = $hash;
        $this->write($registryPath, "<?php\n\nreturn " . var_export($this->registry, true) . ";\n");
    }

    /** @return array<class-string, string> */
    private function getRegistry(): array
    {
        if (null === $this->registry) {
            $registryPath = $this->directory . \DIRECTORY_SEPARATOR . 'registry.php';

            if (!file_exists($registryPath)) {
                $this->registry = [];
            } else {
                $this->registry = require $registryPath;
            }
        }

        return $this->registry;
    }

    private function write(string $file, string $contents): void
    {
        if (!file_exists($this->directory)) {
            mkdir($this->directory);
        }

        $fp = fopen($file, 'w');

        if (false === $fp) {
            throw new \RuntimeException(sprintf('Could not open file "%s"', $file));
        }

        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $contents);
        }

        fclose($fp);
    }
}
