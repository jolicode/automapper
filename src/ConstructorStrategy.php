<?php

declare(strict_types=1);

namespace AutoMapper;

/**
 * Determines how AutoMapper will handle the constructor of the target object.
 */
enum ConstructorStrategy: string
{
    case ALWAYS = 'always';
    case AUTO = 'auto';
    case NEVER = 'never';
}
