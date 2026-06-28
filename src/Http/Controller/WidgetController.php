<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Instance;
use App\Exception\NotFoundException;
use App\Http\Request;
use App\Http\Response;
use App\Renderer;
use App\Store\InstanceRepository;
use App\Widget\WidgetRegistry;

final readonly class WidgetController
{
    public function __construct(
        private InstanceRepository $repo,
        private WidgetRegistry     $registry,
        private Renderer           $renderer,
    ) {
    }

    public function payload(Request $request): Response
    {
        $inst = $this->requireInstance($request);

        try {
            return Response::json($this->renderer->payload($inst));
        } catch (\Throwable $e) {
            return Response::json(['error' => 'render failed'], 500);
        }
    }

    public function data(Request $request): Response
    {
        $inst = $this->requireInstance($request);

        try {
            return Response::json(['data' => $this->renderer->data($inst)]);
        } catch (\Throwable $e) {
            return Response::json(['error' => 'data failed'], 500);
        }
    }

    public function asset(Request $request): Response
    {
        $type = $request->query('type');

        if (!$this->registry->has($type)) {
            return Response::text('', 404);
        }

        return Response::javascript($this->renderer->concat($this->registry->meta($type), 'js'));
    }

    public function types(): Response
    {
        return Response::json(['types' => $this->registry->catalog()]);
    }

    private function requireInstance(Request $request): Instance
    {
        $inst = $this->repo->find($request->query('instance'));

        if (!$inst || !$this->registry->has($inst->type)) {
            throw new NotFoundException();
        }

        return $inst;
    }
}
