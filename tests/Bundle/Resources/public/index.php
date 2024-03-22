<?php

declare(strict_types=1);

use AutoMapper\Tests\Bundle\Resources\App\AppKernel;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../../../../vendor/autoload.php';

$kernel = new AppKernel('prod', false);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
