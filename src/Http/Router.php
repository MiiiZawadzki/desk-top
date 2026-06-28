<?php
declare(strict_types=1);

namespace App\Http;

use App\Exception\HttpError;
use App\Http\Controller\DashboardController;
use App\Http\Controller\InstanceController;
use App\Http\Controller\LayoutController;
use App\Http\Controller\WidgetController;

final class Router
{
    /** @var array<string, array<string, callable(Request): Response>> */
    private array $routes;

    public function __construct(
        DashboardController $dashboard,
        WidgetController $widget,
        InstanceController $instances,
        LayoutController $layout,
        private string $csrf,
    ) {
        $this->routes = [
            '/'              => ['GET' => fn (Request $r) => $dashboard->show()],
            '/api/widget'    => ['GET' => $widget->payload(...)],
            '/api/data'      => ['GET' => $widget->data(...)],
            '/api/asset'     => ['GET' => $widget->asset(...)],
            '/api/types'     => ['GET' => fn (Request $r) => $widget->types()],
            '/api/instances' => [
                'GET'    => fn (Request $r) => $instances->index(),
                'POST'   => $instances->create(...),
                'PATCH'  => $instances->update(...),
                'DELETE' => $instances->delete(...),
            ],
            '/api/layout'    => ['POST' => $layout->save(...)],
        ];
    }

    public function dispatch(Request $request): Response
    {
        try {
            $byMethod = $this->routes[$request->path] ?? null;
            if ($byMethod === null) {
                return Response::text('Not found', 404);
            }

            $method  = $request->method === 'HEAD' ? 'GET' : $request->method;
            $handler = $byMethod[$method] ?? null;

            if ($handler === null) {
                return Response::json(['error' => 'method not allowed'], 405);
            }

            if ($method !== 'GET' && !Csrf::check($request, $this->csrf)) {
                return Response::json(['error' => 'bad csrf'], 403);
            }

            return $handler($request);
        } catch (HttpError $e) {
            return Response::json(['error' => $e->getMessage()], $e->status());
        }
    }
}
