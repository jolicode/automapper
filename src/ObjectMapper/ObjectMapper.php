<?php

namespace AutoMapper\ObjectMapper;

use AutoMapper\AutoMapper;
use AutoMapper\AutoMapperInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class ObjectMapper implements ObjectMapperInterface
{
    private AutoMapperInterface $autoMapper;

    public function __construct(
        ?AutoMapperInterface $autoMapper = null
    )
    {
        $this->autoMapper ??= AutoMapper::create();
    }

    public function map(object $source, object|string|null $target = null): object
    {
        if ($target === null) {
            // @TODO get the target class from attributes
        }

        return $this->autoMapper->map($source, $target);
    }
}