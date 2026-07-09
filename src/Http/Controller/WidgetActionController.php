<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Exception\NotFoundException;
use App\Http\Request;
use App\Http\Response;
use App\Store\InstanceRepository;
use App\Widget\ActionWidgetInterface;
use App\Widget\WidgetContext;
use App\Widget\WidgetRegistry;

/**
 * The single generic dispatcher for widget-owned APIs
 */
final readonly class WidgetActionController
{
    public function __construct(
        private InstanceRepository $repo,
        private WidgetRegistry $registry,
        private string $dataDir,
    ) {
    }

    public function handle(Request $request): Response
    {
        $inst = $this->repo->find($request->query('instance'));
        if (!$inst || !$this->registry->has($inst->type)) {
            throw new NotFoundException();
        }

        $widget = $this->registry->create($inst->type);
        if (!$widget instanceof ActionWidgetInterface) {
            throw new NotFoundException('widget has no actions');
        }

        $ctx = new WidgetContext(
            method: $request->method,
            action: $request->query('action'),
            instance: $inst,
            body: $request->jsonBody(),
            query: $request->queryAll(),
            dataDir: $this->dataDir,
        );

        return Response::json($widget->handleAction($ctx));
    }
}
