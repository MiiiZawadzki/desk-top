<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Request;
use App\Http\Response;
use App\InstanceService;
use App\Store\InstanceRepository;

final readonly class InstanceController
{
    public function __construct(
        private InstanceRepository $repo,
        private InstanceService    $service,
    ) {
    }

    public function index(): Response
    {
        return Response::json(['instances' => $this->repo->all()]);
    }

    public function create(Request $request): Response
    {
        return Response::json(['instance' => $this->service->create($request->jsonBody())], 201);
    }

    public function update(Request $request): Response
    {
        return Response::json(['instance' => $this->service->update($request->query('id'), $request->jsonBody())]);
    }

    public function delete(Request $request): Response
    {
        $this->service->delete($request->query('id'));
        return Response::json(['ok' => true]);
    }
}
