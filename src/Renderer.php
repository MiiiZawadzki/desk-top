<?php
declare(strict_types=1);

namespace App;

use App\Domain\Instance;
use App\Widget\DataWidgetInterface;
use App\Widget\WidgetRegistry;

final readonly class Renderer
{
    public function __construct(private WidgetRegistry $registry) {}

    public function payload(Instance $instance): array
    {
        $widget = $this->registry->create($instance->type);
        $meta   = $this->registry->meta($instance->type);
        $config = $instance->config->toArray();

        $data = $widget instanceof DataWidgetInterface ? $widget->data($config) : null;

        return [
            'type'  => $instance->type,
            'html'  => $widget->render($config),
            'css'   => $this->concat($meta, 'css'),
            'jsUrl' => '/api/asset?type=' . rawurlencode($instance->type),
            'data'  => $data,
        ];
    }

    public function data(Instance $instance): mixed
    {
        $widget = $this->registry->create($instance->type);
        return $widget instanceof DataWidgetInterface ? $widget->data($instance->config->toArray()) : null;
    }

    public function concat(array $meta, string $kind): string
    {
        $out = '';

        foreach ($meta['assets'][$kind] ?? [] as $file) {
            $path = $meta['dir'] . '/' . $file;
            if (is_file($path)) {
                $out .= file_get_contents($path) . "\n";
            }
        }

        return $out;
    }
}
