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
            'face' => [
                'type' => 'select',
                'label' => 'Face',
                'options' => ['Digital', 'Analog', 'Both'],
                'default' => 'Digital',
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
                'label' => 'Clock (digital)',
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
        $mode  = (string)($config['face'] ?? 'Digital');
        $analog  = $mode === 'Analog' || $mode === 'Both';
        $digital = $mode === 'Digital' || $mode === 'Both';
        $mod   = strtolower($mode);

        $utc = ($config['utc'] ?? 'Show') === 'Show'
            ? '<div class="clock__utc"><span class="clock__utc-tag">UTC</span><span data-role="utc">--:--:--</span></div>'
            : '';

        $svg  = $analog ? $this->analogSvg() : '';
        $time = $digital ? '<div class="clock__time" data-role="time">--:--:--</div>' : '';

        return <<<HTML
        <div class="clock clock--{$mod}">
          <span class="clock__label">{$title}</span>
          {$svg}
          {$time}
          <div class="clock__date" data-role="date">…</div>
          {$utc}
        </div>
        HTML;
    }

    private function analogSvg(): string
    {
        $ticks = '';
        for ($i = 0; $i < 12; $i++) {
            $deg   = $i * 30;
            $major = $i % 3 === 0;
            $cls   = $major ? 'clock__tick clock__tick--major' : 'clock__tick';
            $y2    = $major ? 13 : 11;
            $ticks .= "<line class=\"{$cls}\" x1=\"50\" y1=\"5\" x2=\"50\" y2=\"{$y2}\" transform=\"rotate({$deg} 50 50)\"/>";
        }

        return <<<SVG
        <svg class="clock__analog" viewBox="0 0 100 100" role="img" aria-label="Analog clock">
          <circle class="clock__dial" cx="50" cy="50" r="46"/>
          <g class="clock__ticks">{$ticks}</g>
          <line class="clock__hand clock__hand--hour" data-role="hour" x1="50" y1="50" x2="50" y2="30"/>
          <line class="clock__hand clock__hand--min"  data-role="min"  x1="50" y1="50" x2="50" y2="20"/>
          <line class="clock__hand clock__hand--sec"  data-role="sec"  x1="50" y1="50" x2="50" y2="16"/>
          <circle class="clock__hub" cx="50" cy="50" r="3.4"/>
        </svg>
        SVG;
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
