<?php

declare(strict_types=1);

namespace App\Widget;

interface ActionWidgetInterface extends WidgetInterface
{
    public function handleAction(WidgetContext $ctx): array;
}
