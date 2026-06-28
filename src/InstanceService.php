<?php

declare(strict_types=1);

namespace App;

use App\Domain\Instance;
use App\Domain\Layout;
use App\Domain\WidgetConfig;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Log\Logger;
use App\Store\InstanceRepository;
use App\Widget\WidgetRegistry;

final class InstanceService
{
    private const int TITLE_MAX = 120;
    private const int TEXT_MAX = 500;

    public function __construct(
        private readonly InstanceRepository $repo,
        private readonly WidgetRegistry     $registry,
        private readonly Logger             $logger,
    ) {
    }

    public function create(array $body): Instance
    {
        $type = (string)($body['type'] ?? '');
        if (!$this->registry->has($type)) {
            throw new ValidationException('unknown type');
        }

        $config = $this->sanitizeConfig($type, (array)($body['config'] ?? []));
        $size = $this->registry->meta($type)['size'] ?? ['w' => 3, 'h' => 2];
        $title = $this->sanitizeTitle($body['title'] ?? $this->registry->create($type)::label());

        $instance = new Instance(
            id: null,
            type: $type,
            title: $title,
            enabled: true,
            layout: Layout::clamped(1, 1, (int)($size['w'] ?? 3), (int)($size['h'] ?? 2)),
            config: WidgetConfig::fromArray($config),
        );

        $saved = $this->repo->add($instance);
        $this->logger->info('instance.create', ['id' => $saved->id, 'type' => $type]);
        return $saved;
    }

    public function update(string $id, array $body): Instance
    {
        $inst = $this->repo->find($id);
        if (!$inst) {
            throw new NotFoundException();
        }

        if (array_key_exists('title', $body)) {
            $inst = $inst->withTitle($this->sanitizeTitle($body['title']));
        }
        if (array_key_exists('enabled', $body)) {
            $inst = $inst->withEnabled((bool)$body['enabled']);
        }
        if (array_key_exists('config', $body)) {
            $clean = $this->sanitizeConfig($inst->type, (array)$body['config']);
            $inst = $inst->withConfig($inst->config->merge($clean));
        }

        $saved = $this->repo->update($inst);
        $this->logger->info('instance.update', ['id' => $id]);
        return $saved;
    }

    public function delete(string $id): void
    {
        if (!$this->repo->delete($id)) {
            throw new NotFoundException();
        }
        $this->logger->info('instance.delete', ['id' => $id]);
    }

    public function layout(array $body): void
    {
        $byId = [];

        foreach ($body as $p) {
            if (!is_array($p) || !isset($p['id'])) {
                continue;
            }
            $byId[(string)$p['id']] = Layout::clamped(
                (int)($p['x'] ?? 1),
                (int)($p['y'] ?? 1),
                (int)($p['w'] ?? 3),
                (int)($p['h'] ?? 2),
            );
        }

        $this->repo->saveLayout($byId);
        $this->logger->info('instance.layout', ['count' => count($byId)]);
    }

    private function sanitizeConfig(string $type, array $raw): array
    {
        $schema = $this->registry->create($type)::configSchema();
        $clean = [];

        foreach ($schema as $key => $field) {
            if (!array_key_exists($key, $raw)) {
                continue;
            }
            $kind = $field['type'] ?? 'text';
            $val = $raw[$key];

            if ($kind === 'select') {
                $options = $field['options'] ?? [];
                if (in_array($val, $options, true)) {
                    $clean[$key] = $val;
                } elseif (isset($field['default'])) {
                    $clean[$key] = $field['default'];
                }
                continue;
            }

            $clean[$key] = mb_substr((string)$val, 0, self::TEXT_MAX);
        }

        return $clean;
    }

    private function sanitizeTitle(mixed $title): string
    {
        return mb_substr(trim((string)$title), 0, self::TITLE_MAX);
    }
}
