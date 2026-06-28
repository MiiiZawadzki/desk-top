<?php
declare(strict_types=1);

namespace App\Widgets\Demo;

use App\Widget\DataWidgetInterface;

/**
 * DEMO / TEMPLATE WIDGET — a friendly little widget that doubles as the reference
 * for building your own. The visible UI is intentionally human (a greeting, a
 * clock, a rotating tip); the capabilities it quietly demonstrates are:
 *
 *   - config (text + select)          -> see configSchema()
 *   - server-computed data            -> see data()  (greeting/date/tip)
 *   - a live client clock             -> demo.js setInterval (+ cleanup in unmount)
 *   - a "shuffle" that re-fetches data -> demo.js GET /api/data?instance=N
 *   - Shadow-DOM-isolated, self-sizing CSS -> demo.css (container queries)
 *
 * A widget is four files in widgets/<Name>/: manifest.php, <Name>.php (this),
 * <name>.js, <name>.css. Implement WidgetInterface for presentation only, or
 * DataWidgetInterface (this) when you need something the server knows.
 */
final class Demo implements DataWidgetInterface
{
    /** Stable, unique id. MUST equal 'type' in manifest.php. */
    public static function type(): string
    {
        return 'demo';
    }

    /** Human label shown in the "+ Widget" catalog. */
    public static function label(): string
    {
        return 'Demo';
    }

    /**
     * The per-instance settings form is generated from this schema, and the SAME
     * schema is the server-side whitelist when saving — so the form and the
     * validation can never drift apart. Two field types are supported:
     *   'text'   -> a text input (length-capped server-side)
     *   'select' -> a dropdown (value must be one of 'options')
     */
    public static function configSchema(): array
    {
        return [
            'title' => [
                'type'    => 'text',
                'label'   => 'Title',
                'default' => 'Hello',
            ],
            'palette' => [
                'type'    => 'select',
                'label'   => 'Accent colour',
                'options' => ['Indigo', 'Emerald', 'Amber', 'Rose'],
                'default' => 'Indigo',
            ],
        ];
    }

    /**
     * The bare HTML fragment (styling lives in demo.css, injected into the Shadow
     * Root). data-role="…" hooks are how the JS finds its nodes.
     * ALWAYS escape config values — this string is injected into the DOM verbatim.
     */
    public function render(array $config): string
    {
        $title = htmlspecialchars($config['title'] ?? 'Hello', ENT_QUOTES);

        return <<<HTML
        <div class="demo">
          <header class="demo__head">
            <span class="demo__dot"></span>
            <h2 class="demo__title">{$title}</h2>
          </header>

          <p class="demo__greeting" data-role="greeting">Hello there</p>

          <div class="demo__clock" data-role="clock">--:--:--</div>

          <section class="demo__tip">
            <p class="demo__tip-text" data-role="tip">…</p>
            <button type="button" class="demo__btn" data-role="refresh">Show another tip</button>
          </section>
        </div>
        HTML;
    }

    /**
     * Server-computed payload, handed to the JS on mount and returned by
     * GET /api/data?instance=N (the live-refresh endpoint). Recompute everything
     * that should feel "fresh" here — the greeting follows the server clock and a
     * new random tip is picked every call, so clicking "Show another tip" visibly
     * pulls new data from the server.
     */
    public function data(array $config): array
    {
        $palette = (string) ($config['palette'] ?? 'Indigo');
        $hour    = (int) date('G');

        $greeting = $hour < 12 ? 'Good morning'
                  : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        return [
            'greeting' => $greeting . ' · ' . date('l, j F'),
            'tip'      => self::TIPS[array_rand(self::TIPS)],
            'accent'   => self::PALETTES[$palette] ?? self::PALETTES['Indigo'],
        ];
    }

    /** Friendly, plain-language tips about using the dashboard. */
    private const TIPS = [
        'Drag a widget by its handle to move it around.',
        'Grab a corner to resize — your layout saves itself.',
        'Tap the sun in the top bar to switch light and dark.',
        'Add more widgets from the “+ Widget” menu.',
        'Open a widget’s settings with the gear icon.',
        'Everything here updates live — no page reloads.',
    ];

    /** Maps the 'palette' setting to the accent colour the JS applies. */
    private const PALETTES = [
        'Indigo'  => '#818cf8',
        'Emerald' => '#34d399',
        'Amber'   => '#fbbf24',
        'Rose'    => '#fb7185',
    ];
}
