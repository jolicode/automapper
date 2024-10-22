<?php

declare(strict_types=1);

namespace AutoMapper\Loader;

use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\MetadataFactory;
use AutoMapper\Metadata\MetadataRegistry;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;

/**
 * Use eval to load mappers.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class EvalLoader implements ClassLoaderInterface
{
    /** @var array<class-string, true> */
    private static array $lockMap = [];

    public function __construct(
        private readonly MapperGenerator $generator,
        private readonly MetadataFactory $metadataFactory,
        private readonly PrettyPrinterAbstract $printer = new Standard(),
    ) {
    }

    public function loadClass(MapperMetadata $mapperMetadata): void
    {
        if (isset(self::$lockMap[$mapperMetadata->className])) {
            do {
                usleep(100000); // 0.1 second
            } while (isset(self::$lockMap[$mapperMetadata->className])); // @phpstan-ignore isset.offset

            if (class_exists($mapperMetadata->className, false)) {
                return;
            }
        }

        self::$lockMap[$mapperMetadata->className] = true;
        try {
            eval($this->printer->prettyPrint($this->generator->generate(
                $this->metadataFactory->getGeneratorMetadata($mapperMetadata->source, $mapperMetadata->target)
            )));
        } finally {
            unset(self::$lockMap[$mapperMetadata->className]);
        }
    }

    public function buildMappers(MetadataRegistry $registry): bool
    {
        return false;
    }
}
