<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Response;
use App\Http\View;
use App\Store\InstanceRepository;

final readonly class DashboardController
{
    public function __construct(
        private InstanceRepository $repo,
        private string             $csrf,
    ) {
    }

    public function show(): Response
    {
        return Response::html(View::dashboard($this->repo->all(), $this->csrf));
    }
}
