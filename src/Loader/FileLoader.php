<?php

declare(strict_types=1);

namespace AutoMapper\Loader;

use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\MetadataRegistry;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Symfony\Component\VarExporter\ProxyHelper;

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
        $lazyGhostClassName = $mapperMetadata->lazyGhostClassName;

        $classPath = $this->directory . \DIRECTORY_SEPARATOR . $className . '.php';
        $lazyGhostClassPath = $lazyGhostClassName ? $this->directory . \DIRECTORY_SEPARATOR . $lazyGhostClassName . '.php' : null;

        if (!$this->hotReload && file_exists($classPath) && (null === $lazyGhostClassPath || file_exists($lazyGhostClassPath))) {
            if ($lazyGhostClassPath) {
                require $lazyGhostClassPath;
            }

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

        if ($lazyGhostClassPath) {
            require $lazyGhostClassPath;
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

        if ($mapperMetadata->targetReflectionClass !== null && $mapperMetadata->lazyGhostClassName !== null) {
            $lazyGhostClassPath = $this->directory . \DIRECTORY_SEPARATOR . $mapperMetadata->lazyGhostClassName . '.php';
            $lazyGhostClassCode = 'class ' . $mapperMetadata->lazyGhostClassName . ProxyHelper::generateLazyGhost($mapperMetadata->targetReflectionClass);

            $this->write($lazyGhostClassPath, "<?php\n\n" . $lazyGhostClassCode . "\n");
        }

        $generatorMetadata = $this->metadataRegistry->getGeneratorMetadata($mapperMetadata->source, $mapperMetadata->target);
        $classCode = $this->printer->prettyPrint([$this->generator->generate($generatorMetadata)]);

        $this->write($classPath, "<?php\n\n" . $classCode . "\n");
        if ($this->hotReload) {
            $this->addHashToRegistry($className, $mapperMetadata->getHash());
        }

        return $className;
    }

    private function addHashToRegistry($className, $hash): void
    {
        if (null === $this->registry) {
            $this->registry = [];
        }

        $registryPath = $this->directory . \DIRECTORY_SEPARATOR . 'registry.php';
        $this->registry[$className] = $hash;
        $this->write($registryPath, "<?php\n\nreturn " . var_export($this->registry, true) . ";\n");
    }

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

        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $contents);
        }

        fclose($fp);
    }
}
