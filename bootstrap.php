<?php

declare(strict_types=1);

require __DIR__ . '/autoload.php';

use App\Auth\AuthService;
use App\ErrorHandler;
use App\Domain\User;
use App\Http\Controller\AuthController;
use App\Http\Controller\DashboardController;
use App\Http\Controller\InstanceController;
use App\Http\Controller\LayoutController;
use App\Http\Controller\WidgetActionController;
use App\Http\Controller\WidgetController;
use App\Http\Router;
use App\InstanceService;
use App\Log\FileLogger;
use App\Renderer;
use App\Seeder;
use App\Store\SqliteRepository;
use App\Store\SqliteUserRepository;
use App\Widget\WidgetRegistry;

// Ensure the writable data dir exists (a fresh clone tracks only data/.gitkeep,
// but be robust if it's missing too) — SQLite/the logger create files, not dirs.
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0775, true);
}

$logger = new FileLogger($dataDir . '/app.log');
ErrorHandler::register($logger);

// TLS is terminated at the Traefik ingress, so the pod sees plain HTTP; trust
// its X-Forwarded-Proto so the session cookie still gets the Secure flag in prod.
$https = ($_SERVER['HTTPS'] ?? '') !== ''
    || ($_SERVER['SERVER_PORT'] ?? '') === '443'
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => $https,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$registry = new WidgetRegistry();
$registry->discover(__DIR__ . '/widgets');

$dbFile = $dataDir . '/dashboard.sqlite';
$repo = new SqliteRepository($dbFile);
(new Seeder($repo, $registry))->seedIfEmpty();

$users = new SqliteUserRepository($dbFile);
$auth = new AuthService($users);

if ($users->count() === 0) {
    $adminEmail = trim((string)($_SERVER['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: ''));
    $adminPassword = (string)($_SERVER['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD') ?: '');

    if ($adminEmail !== '' && $adminPassword !== '') {
        $users->add(new User(null, $adminEmail, $auth->hash($adminPassword)));
        $logger->info('Seeded initial admin user', ['email' => $adminEmail]);
    }
}

$renderer = new Renderer($registry);
$service = new InstanceService($repo, $registry, $logger);

$csrf = $_SESSION['csrf'];

return new Router(
    new DashboardController($repo, $csrf),
    new WidgetController($repo, $registry, $renderer),
    new WidgetActionController($repo, $registry, $dataDir),
    new InstanceController($repo, $service),
    new LayoutController($service),
    new AuthController($auth, $csrf),
    $csrf,
);
