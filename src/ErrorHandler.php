<?php
declare(strict_types=1);

namespace App;

use App\Http\Response;
use App\Log\Logger;

final class ErrorHandler
{
    public static function register(Logger $logger): void
    {
        error_reporting(E_ALL);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (\Throwable $e) use ($logger): void {
            $logger->error('uncaught', [
                'type'    => $e::class,
                'message' => $e->getMessage(),
                'where'   => $e->getFile() . ':' . $e->getLine(),
            ]);
            self::fail();
        });

        register_shutdown_function(static function () use ($logger): void {
            $err = error_get_last();
            if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $logger->error('fatal', ['message' => $err['message'], 'where' => $err['file'] . ':' . $err['line']]);
                self::fail();
            }
        });
    }

    private static function fail(): void
    {
        if (headers_sent()) {
            return;
        }

        $path  = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $isApi = str_starts_with($path, '/api/');
        ($isApi
            ? Response::json(['error' => 'internal error'], 500)
            : Response::text('Internal Server Error', 500)
        )->send();
    }
}
