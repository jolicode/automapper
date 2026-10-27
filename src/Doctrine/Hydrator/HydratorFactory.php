<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator;

use AutoMapper\Doctrine\Hydrator\Plan\HydrationPlan;
use AutoMapper\Exception\CompileException;
use AutoMapper\Loader\FileReloadStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use PhpParser\PrettyPrinter\Standard;

/**
 * Resolves, generates and loads the hydrator of a result set mapping.
 *
 * Like the mappers, generated hydrators are written in the cache directory, or evaluated when there is none.
 * With {@see FileReloadStrategy::NEVER} the result set mapping is not even analyzed once its hydrator exists.
 */
final class HydratorFactory
{
    /**
     * Hints for which the ObjectHydrator is always used.
     */
    private const UNSUPPORTED_HINTS = [
        Query::HINT_REFRESH,
        Query::HINT_REFRESH_ENTITY,
        Query::HINT_CACHE_ENABLED,
    ];

    private const INDEX_FILE = 'hydrators.php';

    /** @var list<string>|null */
    private static ?array $rsmProperties = null;

    /** @var array<string, class-string<GeneratedHydrator>|false> result set signature => class, false when unsupported */
    private array $classes = [];

    /** @var array<string, class-string<GeneratedHydrator>|false>|null */
    private ?array $index = null;

    /** @var \WeakMap<EntityManagerInterface, array<class-string<GeneratedHydrator>, GeneratedHydrator>> */
    private \WeakMap $instances;

    /** @var array<string, string> result set signature => reason */
    private array $unsupportedReasons = [];

    /** @var array<string, array{generated: int, fallback: int}> */
    private array $statistics = [];

    public function __construct(
        private readonly ?string $cacheDirectory = null,
        private readonly FileReloadStrategy $reloadStrategy = FileReloadStrategy::ON_CHANGE,
        private readonly ResultSetMappingAnalyzer $analyzer = new ResultSetMappingAnalyzer(),
        private readonly HydratorGenerator $generator = new HydratorGenerator(),
    ) {
        $this->instances = new \WeakMap();
    }

    /**
     * @param array<string, mixed> $hints
     * @param string               $mode  one of the HydrationPlan modes
     */
    public function getHydrator(EntityManagerInterface $em, ResultSetMapping $rsm, array $hints, string $mode = HydrationPlan::OBJECT): ?GeneratedHydrator
    {
        $this->statistics[$mode] ??= ['generated' => 0, 'fallback' => 0];

        foreach (self::UNSUPPORTED_HINTS as $hint) {
            if (isset($hints[$hint]) && false !== $hints[$hint]) {
                ++$this->statistics[$mode]['fallback'];

                return null;
            }
        }

        $className = $this->getClass($em, $rsm, $mode);

        if (false === $className) {
            ++$this->statistics[$mode]['fallback'];

            return null;
        }

        ++$this->statistics[$mode]['generated'];

        $instances = $this->instances[$em] ?? [];

        if (!isset($instances[$className])) {
            $instances[$className] = new $className($em);
            $this->instances[$em] = $instances;
        }

        return $instances[$className];
    }

    /**
     * How many hydrations used a generated hydrator or fell back on the Doctrine one, per hydration mode.
     *
     * @return array<string, array{generated: int, fallback: int}>
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * Why the ObjectHydrator is used for this result set mapping, null when a generated hydrator is used.
     */
    public function getUnsupportedReason(EntityManagerInterface $em, ResultSetMapping $rsm, string $mode = HydrationPlan::OBJECT): ?string
    {
        return $this->unsupportedReasons[$this->signature($em, $rsm, $mode)] ?? null;
    }

    /**
     * @return class-string<GeneratedHydrator>|false
     */
    private function getClass(EntityManagerInterface $em, ResultSetMapping $rsm, string $mode): string|false
    {
        $signature = $this->signature($em, $rsm, $mode);

        return $this->classes[$signature] ??= $this->load($em, $rsm, $signature, $mode);
    }

    private function signature(EntityManagerInterface $em, ResultSetMapping $rsm, string $mode): string
    {
        // only the mapping itself, a ResultSetMappingBuilder also holds the entity manager
        $mapping = [];

        foreach (self::$rsmProperties ??= array_map(static fn (\ReflectionProperty $property) => $property->getName(), (new \ReflectionClass(ResultSetMapping::class))->getProperties(\ReflectionProperty::IS_PUBLIC)) as $property) {
            $mapping[] = $rsm->$property;
        }

        return hash('xxh128', $mode . $em->getConnection()->getDatabasePlatform()::class . serialize($mapping));
    }

    /**
     * @return class-string<GeneratedHydrator>|false
     */
    private function load(EntityManagerInterface $em, ResultSetMapping $rsm, string $signature, string $mode): string|false
    {
        if (null !== $this->cacheDirectory && FileReloadStrategy::NEVER === $this->reloadStrategy) {
            $className = $this->readIndex()[$signature] ?? null;

            if (false === $className) {
                return false;
            }

            if (null !== $className && (class_exists($className, false) || $this->requireClass($className))) {
                return $className;
            }
        }

        try {
            $plan = $this->analyzer->analyze($rsm, $em, $mode);
        } catch (UnsupportedResultSetMappingException $e) {
            $this->unsupportedReasons[$signature] = $e->getMessage();
            $this->addToIndex($signature, false);

            return false;
        }

        /** @var class-string<GeneratedHydrator> $className */
        $className = 'AutoMapperHydrator_' . $plan->hash();

        if (class_exists($className, false)) {
            return $className;
        }

        if (null === $this->cacheDirectory) {
            eval($this->generate($plan, $className, $em));

            return $className;
        }

        if (FileReloadStrategy::ALWAYS === $this->reloadStrategy || !$this->requireClass($className)) {
            $this->write($this->classPath($className), "<?php\n\n" . $this->generate($plan, $className, $em) . "\n");
            require $this->classPath($className);
        }

        $this->addToIndex($signature, $className);

        return $className;
    }

    private function generate(HydrationPlan $plan, string $className, EntityManagerInterface $em): string
    {
        return (new Standard())->prettyPrint([$this->generator->generate($plan, $className, $em->getConnection()->getDatabasePlatform())]);
    }

    private function requireClass(string $className): bool
    {
        $path = $this->classPath($className);

        if (!file_exists($path)) {
            return false;
        }

        require $path;

        return true;
    }

    private function classPath(string $className): string
    {
        return $this->cacheDirectory . \DIRECTORY_SEPARATOR . $className . '.php';
    }

    /**
     * @return array<string, class-string<GeneratedHydrator>|false>
     */
    private function readIndex(): array
    {
        if (null !== $this->index) {
            return $this->index;
        }

        $path = $this->cacheDirectory . \DIRECTORY_SEPARATOR . self::INDEX_FILE;
        $index = file_exists($path) ? require $path : [];

        /** @var array<string, class-string<GeneratedHydrator>|false> $index */
        return $this->index = \is_array($index) ? $index : [];
    }

    /**
     * @param class-string<GeneratedHydrator>|false $className
     */
    private function addToIndex(string $signature, string|false $className): void
    {
        if (null === $this->cacheDirectory || FileReloadStrategy::NEVER !== $this->reloadStrategy) {
            return;
        }

        // re-read from disk so entries written by other processes are kept
        $this->index = null;
        $index = $this->readIndex();

        if (($index[$signature] ?? null) === $className) {
            return;
        }

        $index[$signature] = $className;
        $this->index = $index;
        $this->write($this->cacheDirectory . \DIRECTORY_SEPARATOR . self::INDEX_FILE, "<?php\n\nreturn " . var_export($index, true) . ";\n");
    }

    private function write(string $file, string $contents): void
    {
        $directory = \dirname($file);

        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new CompileException(\sprintf('Could not create directory "%s"', $directory));
        }

        // the rename is atomic, a concurrent process never requires a partially written file
        $tmpFile = tempnam($directory, 'am_');

        if (false === $tmpFile || false === @file_put_contents($tmpFile, $contents) || !@rename($tmpFile, $file)) {
            throw new CompileException(\sprintf('Could not write file "%s"', $file));
        }

        @chmod($file, 0666 & ~umask());
    }
}
