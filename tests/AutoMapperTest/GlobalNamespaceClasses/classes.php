<?php

declare(strict_types=1);

// Classes in the global namespace on purpose: class names resolved from their docblocks
// get a leading backslash which must not break the generated mappers.

class AutoMapperGlobalItemSource
{
    public string $name = '';
}

class AutoMapperGlobalSource
{
    /** @var AutoMapperGlobalItemSource[] */
    public array $items = [];
}

class AutoMapperGlobalItemDto
{
    public string $name = '';
}

class AutoMapperGlobalDto
{
    /** @var AutoMapperGlobalItemDto[] */
    public array $items = [];
}
