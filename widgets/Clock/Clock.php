<?php

declare(strict_types=1);

namespace App\Widgets\Clock;

use App\Widget\DataWidgetInterface;

final class Clock implements DataWidgetInterface
{
    public static function type(): string
    {
        return 'clock';
    }

    public static function label(): string
    {
        return 'Clock';
    }

    public static function configSchema(): array
    {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Label',
                'default' => 'Local time',
            ],
            'timezone' => [
                'type' => 'select',
                'label' => 'Timezone',
                'options' => [
                    'Local',
                    'UTC',
                    'Europe/Warsaw',
                    'Europe/London',
                    'America/New_York',
                    'America/Los_Angeles',
                    'Asia/Tokyo',
                    'Australia/Sydney',
                ],
                'default' => 'Local',
            ],
            'clock' => [
                'type' => 'select',
                'label' => 'Clock',
                'options' => ['24-hour', '12-hour'],
                'default' => '24-hour',
            ],
            'utc' => [
                'type' => 'select',
                'label' => 'Server (UTC) line',
                'options' => ['Show', 'Hide'],
                'default' => 'Show',
            ],
        ];
    }

    public function render(array $config): string
    {
        $title = htmlspecialchars($config['title'] ?? 'Local time', ENT_QUOTES);

        $utc = ($config['utc'] ?? 'Show') === 'Show'
            ? '<div class="clock__utc"><span class="clock__utc-tag">UTC</span><span data-role="utc">--:--:--</span></div>'
            : '';

        return <<<HTML
        <div class="clock">
          <span class="clock__label">{$title}</span>
          <div class="clock__time" data-role="time">--:--:--</div>
          {$utc}
          <div class="clock__date" data-role="date">…</div>
        </div>
        HTML;
    }

    public function data(array $config): array
    {
        return [
            'timezone' => (string)($config['timezone'] ?? 'Local'),
            'hour12' => ($config['clock'] ?? '24-hour') === '12-hour',
            'serverNow' => (int)round(microtime(true) * 1000),
        ];
    }
}
