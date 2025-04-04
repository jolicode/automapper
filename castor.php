<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\PHPQa\php_cs_fixer;
use function Castor\PHPQa\phpstan;

#[AsTask('cs:check', namespace: 'qa', description: 'Check for coding standards without fixing them')]
function qa_cs_check()
{
    php_cs_fixer(['fix', '--config', __DIR__ . '/.php-cs-fixer.php', '--dry-run', '--diff'], '3.50', [
        'kubawerlos/php-cs-fixer-custom-fixers' => '^3.21',
    ]);
}

#[AsTask('cs:fix', namespace: 'qa', description: 'Fix all coding standards', aliases: ['cs'])]
function qa_cs_fix()
{
    php_cs_fixer(['fix', '--config', __DIR__ . '/.php-cs-fixer.php', '-v'], '3.50', [
        'kubawerlos/php-cs-fixer-custom-fixers' => '^3.21',
    ]);
}

#[AsTask('phpstan', namespace: 'qa', description: 'Run PHPStan for static analysis', aliases: ['phpstan'])]
function qa_phpstan(bool $generateBaseline = false)
{
    $params = ['analyse', '--configuration', __DIR__ . '/phpstan.neon', '--memory-limit=-1', '-v'];
    if ($generateBaseline) {
        $params[] = '--generate-baseline';
    }

    phpstan($params, '1.11.1');
}

#[AsTask('mapper', namespace: 'debug', description: 'Debug a mapper', aliases: ['debug'])]
function debug_mapper(string $source, string $target, string $load = '')
{
    require_once __DIR__ . '/vendor/autoload.php';

    if ($load) {
        require_once $load;
    }

    $automapper = AutoMapper\AutoMapper::create();
    // get private property loader value
    $loader = new ReflectionProperty($automapper, 'classLoader');
    $loader = $loader->getValue($automapper);

    // get metadata factory
    $metadataFactory = new ReflectionProperty($loader, 'metadataFactory');
    $metadataFactory = $metadataFactory->getValue($loader);

    $command = new AutoMapper\Symfony\Bundle\Command\DebugMapperCommand($metadataFactory);
    $input = new Symfony\Component\Console\Input\ArrayInput([
        'source' => $source,
        'target' => $target,
    ]);

    $command->run($input, \Castor\output());
}
