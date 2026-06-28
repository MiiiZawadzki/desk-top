<?php
declare(strict_types=1);

namespace App\Widget;

final class WidgetRegistry
{
    /** @var array<string, array> */
    private array $types = [];

    public function discover(string $dir): void
    {
        foreach (glob($dir . '/*/manifest.php') as $file) {
            $meta = require $file;
            $meta['dir'] = dirname($file);
            $this->types[$meta['type']] = $meta;
        }
    }

    public function has(string $type): bool
    {
        return isset($this->types[$type]);
    }

    public function meta(string $type): array
    {
        if (!isset($this->types[$type])) {
            throw new \RuntimeException("Unknown widget type: {$type}");
        }
        return $this->types[$type];
    }

    /** @return array<string, array> */
    public function all(): array
    {
        return $this->types;
    }

    /** @return array<int, array{type:string,label:string,size:array,configSchema:array}> */
    public function catalog(): array
    {
        $out = [];
        foreach ($this->types as $type => $meta) {
            $class = $meta['class'];
            $out[] = [
                'type'         => $type,
                'label'        => $class::label(),
                'size'         => $meta['size'] ?? ['w' => 3, 'h' => 2],
                'configSchema' => $class::configSchema(),
            ];
        }
        return $out;
    }

    public function create(string $type): WidgetInterface
    {
        if (!isset($this->types[$type])) {
            throw new \RuntimeException("Unknown widget type: {$type}");
        }
        $class = $this->types[$type]['class'];
        return new $class();
    }
}
