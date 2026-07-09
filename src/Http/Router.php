<?php
declare(strict_types=1);

namespace App\Http;

use App\Exception\HttpError;
use App\Http\Controller\AuthController;
use App\Http\Controller\DashboardController;
use App\Http\Controller\InstanceController;
use App\Http\Controller\LayoutController;
use App\Http\Controller\WidgetActionController;
use App\Http\Controller\WidgetController;

final class Router
{
    /** @var array<string, array<string, callable(Request): Response>> */
    private array $routes;

    /**
     * Routes reachable without a session. Everything else requires a logged-in user
     *
     * @var array<string, true>
     */
    private const array PUBLIC_PATHS = ['/login' => true];

    public function __construct(
        DashboardController $dashboard,
        WidgetController $widget,
        WidgetActionController $widgetAction,
        InstanceController $instances,
        LayoutController $layout,
        AuthController $auth,
        private string $csrf,
    ) {
        $this->routes = [
            '/'              => ['GET' => fn (Request $r) => $dashboard->show()],
            '/login'         => [
                'GET'  => fn (Request $r) => $auth->showLogin(),
                'POST' => $auth->login(...),
            ],
            '/logout'        => ['POST' => fn (Request $r) => $auth->logout()],
            '/api/widget'    => ['GET' => $widget->payload(...)],
            '/api/data'      => ['GET' => $widget->data(...)],
            '/api/asset'     => ['GET' => $widget->asset(...)],
            '/api/types'     => ['GET' => fn (Request $r) => $widget->types()],
            // generic dispatcher for widget-owned APIs
            '/api/widget/action' => [
                'GET'    => $widgetAction->handle(...),
                'POST'   => $widgetAction->handle(...),
                'PATCH'  => $widgetAction->handle(...),
                'DELETE' => $widgetAction->handle(...),
            ],
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

            // Auth gate: everything outside PUBLIC_PATHS needs a session.
            if (!isset(self::PUBLIC_PATHS[$request->path]) && empty($_SESSION['user_id'])) {
                if ($method === 'GET' && !str_starts_with($request->path, '/api/')) {
                    return Response::redirect('/login');
                }
                return Response::json(['error' => 'unauthorized'], 401);
            }

            return $handler($request);
        } catch (HttpError $e) {
            return Response::json(['error' => $e->getMessage()], $e->status());
        }
    }
}
