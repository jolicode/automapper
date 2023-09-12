<?php

declare(strict_types=1);

namespace AutoMapper\Loader;

use AutoMapper\Generator\Generator;
use AutoMapper\MapperGeneratorMetadataInterface;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;

/**
 * Use eval to load mappers.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class EvalLoader implements ClassLoaderInterface
{
    private PrettyPrinterAbstract $printer;

    public function __construct(
        private Generator $generator,
    ) {
        $this->printer = new Standard();
    }

    public function loadClass(MapperGeneratorMetadataInterface $mapperMetadata): void
    {
        $class = $this->generator->generate($mapperMetadata);

        eval($this->printer->prettyPrint([$class]));
    }
}
