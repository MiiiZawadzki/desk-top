<?php
declare(strict_types=1);

namespace App\Widget;

interface WidgetInterface
{
    public static function type(): string;
    public static function label(): string;
    public static function configSchema(): array;
    public function render(array $config): string;
}
