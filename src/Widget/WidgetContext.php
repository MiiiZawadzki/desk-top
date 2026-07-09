<?php

declare(strict_types=1);

namespace App\Widget;

use App\Domain\Instance;

/**
 * The request context handed to a widget
 */
final readonly class WidgetContext
{
    public function __construct(
        public string   $method,    // GET | POST | PATCH | DELETE
        public string   $action,    // the ?action= command (add, toggle, …)
        public Instance $instance,  // resolved + type-matched to this widget
        public array    $body,      // decoded JSON body
        public array    $query,     // all query params
        public string   $dataDir,   // absolute path to the app data dir (for own storage)
    ) {
    }
}
