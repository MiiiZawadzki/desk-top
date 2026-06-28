<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\Widgets\\' => __DIR__ . '/widgets/',
        'App\\' => __DIR__ . '/src/',
    ];
    foreach ($prefixes as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $rel = substr($class, strlen($prefix));
            $path = $base . str_replace('\\', '/', $rel) . '.php';
            if (is_file($path)) {
                require $path;
                return;
            }
        }
    }
});
