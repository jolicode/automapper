<?php

declare(strict_types=1);

use AutoMapper\Tests\Bundle\Resources\App\AppKernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../../../../vendor/autoload.php';

Debug::enable();
$kernel = new AppKernel('dev', true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
