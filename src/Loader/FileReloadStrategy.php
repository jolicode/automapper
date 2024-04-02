<?php

declare(strict_types=1);

namespace AutoMapper\Loader;

enum FileReloadStrategy: string
{
    case ALWAYS = 'always';
    case NEVER = 'never';
    case ON_CHANGE = 'on_change';
}
