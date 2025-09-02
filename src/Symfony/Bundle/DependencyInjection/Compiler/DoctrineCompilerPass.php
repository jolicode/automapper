<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\DependencyInjection\Compiler;

use AutoMapper\EventListener\Doctrine\DoctrineIdentifierListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('automapper.doctrine.object_manager')) {
            return;
        }

        if (!$container->has(DoctrineIdentifierListener::class)) {
            return;
        }

        if ($container->has('doctrine.orm.entity_manager')) {
            $container->setAlias('automapper.doctrine.object_manager', 'doctrine.orm.entity_manager');
        } elseif ($container->has('doctrine_mongodb.odm.document_manager')) {
            $container->setAlias('automapper.doctrine.object_manager', 'doctrine_mongodb.odm.document_manager');
        } else {
            throw new \LogicException('The AutoMapper Doctrine integration is enabled but no Doctrine ObjectManager service was found. Please configure the "automapper.doctrine.object_manager" service alias to point to your Doctrine ObjectManager service.');
        }
    }
}
