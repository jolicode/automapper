<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(
        string $environment,
        bool $debug,
        private ?string $additionalConfigFile = null,
    ) {
        parent::__construct($environment, $debug);
    }

    protected function buildContainer(): ContainerBuilder
    {
        $containerBuilder = parent::buildContainer();

        if ($this->additionalConfigFile) {
            $this->getContainerLoader($containerBuilder)->load($this->additionalConfigFile);
        }

        return $containerBuilder;
    }

    public function getProjectDir(): string
    {
        return __DIR__ . '/..';
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir() . '/' . md5($this->additionalConfigFile ?? '');
    }
}
