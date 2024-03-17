<?php

declare(strict_types=1);

namespace AutoMapper;

class AutoMapperDebug
{
    public ?string $mapperGuessedSource = null;
    public ?string $mapperGuessedTarget = null;
    public ?string $mapperClassName = null;
    public bool $mapperWasAlreadyGenerated = false;
}
