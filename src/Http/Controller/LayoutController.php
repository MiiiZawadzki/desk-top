<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Request;
use App\Http\Response;
use App\InstanceService;

final readonly class LayoutController
{
    public function __construct(private InstanceService $service)
    {
    }

    public function save(Request $request): Response
    {
        $this->service->layout($request->jsonBody());
        return Response::json(['ok' => true]);
    }
}
