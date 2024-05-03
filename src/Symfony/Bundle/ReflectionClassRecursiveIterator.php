<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle;

/**
 * @internal
 */
final class ReflectionClassRecursiveIterator
{
    private function __construct()
    {
    }

    /**
     * @param array<string> $directories
     *
     * @return iterable<class-string<object>, \ReflectionClass<object>>
     */
    public static function getReflectionClassesFromDirectories(array $directories): iterable
    {
        foreach ($directories as $path) {
            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+\.php$/i',
                \RecursiveRegexIterator::GET_MATCH
            );

            /** @var array{string} $file */
            foreach ($iterator as $file) {
                $sourceFile = $file[0];

                if (!preg_match('(^phar:)i', (string) $sourceFile)) {
                    $sourceFile = realpath($sourceFile);
                }

                try {
                    require_once $sourceFile;
                } catch (\Throwable) {
                    // invalid PHP file (example: missing parent class)
                    continue;
                }

                $includedFiles[$sourceFile] = true;
            }
        }

        $sortedClasses = get_declared_classes();
        sort($sortedClasses);
        $sortedInterfaces = get_declared_interfaces();
        sort($sortedInterfaces);
        $declared = [...$sortedClasses, ...$sortedInterfaces];
        foreach ($declared as $className) {
            $reflectionClass = new \ReflectionClass($className);
            $sourceFile = $reflectionClass->getFileName();
            if (isset($includedFiles[$sourceFile])) {
                yield $className => $reflectionClass;
            }
        }
    }
}
