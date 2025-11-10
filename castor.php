<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\PHPQa\php_cs_fixer;
use function Castor\PHPQa\phpstan;
use function Castor\run;

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

#[AsTask('install', namespace: 'doc', description: 'Install tool for documentation (need poetry)')]
function doc_install()
{
    run('poetry install');
}

#[AsTask('server', namespace: 'doc', description: 'Serve documentation')]
function doc_serve()
{
    run('poetry run mkdocs serve -a localhost:8000');
}

#[AsTask('build-github-pages', namespace: 'doc', description: 'Serve documentation')]
function doc_build_github_pages()
{
    // clean .build directory
    run('rm -rf ./.build', context: context()->withAllowFailure());

    // create .build directory
    @mkdir(__DIR__ . '/.build');
    run('git config user.name ci-bot');
    run('git config user.email ci-bot@example.org');
    run('git remote add gh-pages ./.build', context: context()->withAllowFailure());

    $context = context()->withWorkingDirectory(__DIR__ . '/.build');

    run('git init', context: $context);
    run('git checkout -b gh-pages', context: $context);
    run('git config receive.denyCurrentBranch ignore', context: $context);

    // build documentation for main branch
    run('poetry run mike deploy --push --remote gh-pages dev');

    // get the list of tags
    $tags = run('git tag --list', context: context()->withQuiet())->getOutput();
    $tags = array_filter(array_map('trim', explode("\n", $tags)));

    $minVersion = '8.2.2';
    $latestVersion = $minVersion;
    $buildTags = [];

    foreach ($tags as $tag) {
        if (!version_compare($tag, $minVersion, '>=')) {
            continue;
        }

        // version is X.Y.Z we want the X.Y part
        $parts = explode('.', $tag);
        if (count($parts) !== 3) {
            continue;
        }

        $majorMinor = $parts[0] . '.' . $parts[1];

        // get only the last version for this major.minor
        if (isset($buildTags[$majorMinor]) && version_compare($tag, $buildTags[$majorMinor], '<=')) {
            continue;
        }

        if (version_compare($tag, $latestVersion, '>')) {
            $latestVersion = $tag;
        }

        $buildTags[$majorMinor] = $tag;
    }

    foreach ($buildTags as $tag) {
        run('git checkout tags/' . $tag);

        if ($tag === $latestVersion) {
            run('poetry run mike deploy --push --remote gh-pages ' . $tag . ' latest');
        } else {
            run('poetry run mike deploy --push --remote gh-pages --update-aliases ' . $tag);
        }
    }

    run('git reset --hard gh-pages', context: $context);
}
