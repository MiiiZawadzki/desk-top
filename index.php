<?php

declare(strict_types=1);

use App\Http\Request;

/** @var App\Http\Router $router */
$router = require __DIR__ . '/bootstrap.php';

$router->dispatch(Request::fromGlobals())->send();
