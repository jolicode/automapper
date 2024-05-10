<?php

declare(strict_types=1);

namespace AutoMapper\Loader;

use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\MetadataFactory;
use AutoMapper\Metadata\MetadataRegistry;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * Use eval to load mappers.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 *  @internal
 */
final readonly class EvalLoader implements ClassLoaderInterface
{
    private PrettyPrinterAbstract $printer;

    public function __construct(
        private MapperGenerator $generator,
        private MetadataFactory $metadataFactory,
    ) {
        $this->printer = new Standard();
    }

    public function loadClass(MapperMetadata $mapperMetadata): void
    {
        if ($mapperMetadata->targetReflectionClass !== null && $mapperMetadata->lazyGhostClassName !== null) {
            $lazyGhostClass = 'class ' . $mapperMetadata->lazyGhostClassName . ProxyHelper::generateLazyGhost($mapperMetadata->targetReflectionClass);
            eval($lazyGhostClass);
        }

        $class = $this->generator->generate($this->metadataFactory->getGeneratorMetadata($mapperMetadata->source, $mapperMetadata->target));

        eval($this->printer->prettyPrint([$class]));
    }

    public function buildMappers(MetadataRegistry $registry): bool
    {
        return false;
    }
}
