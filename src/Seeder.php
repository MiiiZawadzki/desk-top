<?php

declare(strict_types=1);

namespace App;

use App\Domain\Instance;
use App\Domain\Layout;
use App\Domain\WidgetConfig;
use App\Store\InstanceRepository;
use App\Widget\WidgetRegistry;

final readonly class Seeder
{
    public function __construct(
        private InstanceRepository $repo,
        private WidgetRegistry $registry,
    ) {
    }

    public function seedIfEmpty(): void
    {
        if ($this->repo->all() !== []) {
            return;
        }

        $catalog = $this->registry->catalog();
        if ($catalog === []) {
            return;
        }
        $type = $catalog[0];

        $config = [];
        foreach ($type['configSchema'] as $key => $field) {
            if (isset($field['default'])) {
                $config[$key] = $field['default'];
            }
        }

        $size = $type['size'] ?? ['w' => 3, 'h' => 2];

        $this->repo->add(
            new Instance(
                id: null,
                type: $type['type'],
                title: $config['title'] ?? $type['label'],
                enabled: true,
                layout: Layout::clamped(1, 1, (int)($size['w'] ?? 3), (int)($size['h'] ?? 2)),
                config: WidgetConfig::fromArray($config),
            )
        );
    }
}
