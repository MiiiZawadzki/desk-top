<?php
declare(strict_types=1);

namespace App\Widget;

interface DataWidgetInterface extends WidgetInterface
{
    public function data(array $config): array;
}
