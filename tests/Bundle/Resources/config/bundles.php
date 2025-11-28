<?php

declare(strict_types=1);

$bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['dev' => true],
    AutoMapper\Symfony\Bundle\AutoMapperBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
];

if (class_exists(ApiPlatform\Symfony\Bundle\ApiPlatformBundle::class)) {
    $bundles[ApiPlatform\Symfony\Bundle\ApiPlatformBundle::class] = ['all' => true];
}

return $bundles;
