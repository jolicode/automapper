<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\PHPQa\php_cs_fixer;
use function Castor\PHPQa\phpstan;

#[AsTask('cs:check', namespace: 'qa', description: 'Check for coding standards without fixing them')]
function qa_cs_check()
{
    php_cs_fixer(['fix', '--config', __DIR__ . '/.php-cs-fixer.php', '--dry-run', '--diff'], '3.85.1', [
        'kubawerlos/php-cs-fixer-custom-fixers' => '^3.21',
    ]);
}

#[AsTask('cs:fix', namespace: 'qa', description: 'Fix all coding standards', aliases: ['cs'])]
function qa_cs_fix()
{
    php_cs_fixer(['fix', '--config', __DIR__ . '/.php-cs-fixer.php', '-v'], '3.85.1', [
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

    phpstan($params, '1.12.23');
}

#[AsTask('mapper', namespace: 'debug', description: 'Debug a mapper', aliases: ['debug'])]
function debug_mapper(string $source, string $target, string $load = '')
{
    require_once __DIR__ . '/vendor/autoload.php';

    // special autoloader for "AutoMapperTests"
    spl_autoload_register(function (string $class) {
        if (!str_starts_with($class, 'AutoMapper\\Tests\\AutoMapperTest\\')) {
            return false;
        }

        // split on namespace separator
        $parts = explode('\\', $class);
        // get second part
        $testDirectory = $parts[3] ?? '';
        $mapFile = __DIR__ . '/tests/AutoMapperTest/' . $testDirectory . '/map.php';

        if (file_exists($mapFile)) {
            require_once $mapFile;
        }
    });

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
