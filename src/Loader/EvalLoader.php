<?php

declare(strict_types=1);

namespace AutoMapper\Loader;

use AutoMapper\Generator\MapperGenerator;
use AutoMapper\MapperMetadata\MapperGeneratorMetadataInterface;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;

/**
 * Use eval to load mappers.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final readonly class EvalLoader implements ClassLoaderInterface
{
    private PrettyPrinterAbstract $printer;

    public function __construct() {
        $this->printer = new Standard();
    }

    public function loadClass(MapperGenerator $mapperGenerator, MapperGeneratorMetadataInterface $mapperMetadata): void
    {
        $class = $mapperGenerator->generate($mapperMetadata);

        eval($this->printer->prettyPrint([$class]));
    }
}
