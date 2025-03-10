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
    php_cs_fixer(['fix', '--config', __DIR__ . '/.php-cs-fixer.php'], '3.50', [
        'kubawerlos/php-cs-fixer-custom-fixers' => '^3.21',
    ]);
}

#[AsTask('phpstan', namespace: 'qa', description: 'Run PHPStan for static analysis', aliases: ['phpstan'])]
function qa_phpstan()
{
    phpstan(['analyse', '--configuration', __DIR__ . '/phpstan.neon', '--memory-limit=-1'], '1.11.1');
}
