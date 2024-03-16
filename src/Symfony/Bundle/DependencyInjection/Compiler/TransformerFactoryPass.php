<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\DependencyInjection\Compiler;

use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\CustomTransformer\CustomTransformersRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TransformerFactoryPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        $selectors = [];

        foreach ($this->findAndSortTaggedServices('automapper.transformer_factory', $container) as $definition) {
            $selectors[] = $definition;
        }

        $definition = $container->getDefinition(ChainTransformerFactory::class);
        $definition->replaceArgument(0, $selectors);

        $registry = $container->getDefinition(CustomTransformersRegistry::class);

        foreach ($this->findAndSortTaggedServices('automapper.custom_transformer', $container) as $definition) {
            $registry->addMethodCall('addCustomTransformer', [$definition]);
        }
    }
}
