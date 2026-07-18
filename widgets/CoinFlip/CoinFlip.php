<?php

declare(strict_types=1);

namespace App\Widgets\CoinFlip;

use App\Widget\WidgetInterface;

final class CoinFlip implements WidgetInterface
{
    public static function type(): string
    {
        return 'coinflip';
    }

    public static function label(): string
    {
        return 'Coin Flip';
    }

    public static function configSchema(): array
    {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Label',
                'default' => 'Coin Flip',
            ],
            'heads' => [
                'type' => 'text',
                'label' => 'Heads label',
                'default' => 'Heads',
            ],
            'headsSymbol' => [
                'type' => 'text',
                'label' => 'Heads symbol',
                'default' => '♛',
            ],
            'tails' => [
                'type' => 'text',
                'label' => 'Tails label',
                'default' => 'Tails',
            ],
            'tailsSymbol' => [
                'type' => 'text',
                'label' => 'Tails symbol',
                'default' => '⚜',
            ],
        ];
    }

    public function render(array $config): string
    {
        $title = htmlspecialchars($config['title'] ?? 'Coin Flip', ENT_QUOTES);
        $heads = htmlspecialchars($config['heads'] ?? 'Heads', ENT_QUOTES);
        $tails = htmlspecialchars($config['tails'] ?? 'Tails', ENT_QUOTES);
        $hSym = htmlspecialchars($config['headsSymbol'] ?? '♛', ENT_QUOTES);
        $tSym = htmlspecialchars($config['tailsSymbol'] ?? '⚜', ENT_QUOTES);

        return <<<HTML
        <div class="cf">
          <span class="cf__title">{$title}</span>

          <div class="cf__stage">
            <div class="cf__coin" data-role="coin" role="button" tabindex="0" aria-label="Flip the coin">
              <div class="cf__face cf__face--heads">
                <span class="cf__sigil" aria-hidden="true">{$hSym}</span>
                <span class="cf__label">{$heads}</span>
              </div>
              <div class="cf__face cf__face--tails">
                <span class="cf__sigil" aria-hidden="true">{$tSym}</span>
                <span class="cf__label">{$tails}</span>
              </div>
            </div>
          </div>

          <div class="cf__result" data-role="result" aria-live="polite">&nbsp;</div>
          <button type="button" class="cf__btn" data-role="flip">Flip</button>

          <div class="cf__stats">
            <span class="cf__tally" data-role="tally"></span>
            <button type="button" class="cf__reset" data-role="reset" aria-label="Reset stats" title="Reset stats">⟲</button>
          </div>
        </div>
        HTML;
    }
}
