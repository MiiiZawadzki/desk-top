<?php

declare(strict_types=1);

namespace App\Http;

final class Csrf
{
    public static function check(Request $request, string $expected): bool
    {
        if (!self::sameOrigin($request)) {
            return false;
        }

        $got = $request->csrfToken();
        return $got !== '' && hash_equals($expected, $got);
    }

    private static function sameOrigin(Request $request): bool
    {
        $host = $request->host();
        $src = $request->origin() !== '' ? $request->origin() : $request->referer();
        if ($src === '') {
            return true;
        }

        $srcHost = parse_url($src, PHP_URL_HOST);
        if ($srcHost === null || $srcHost === false) {
            return false;
        }

        $port = parse_url($src, PHP_URL_PORT);
        if ($port) {
            $srcHost .= ':' . $port;
        }

        return $host !== '' && hash_equals($host, $srcHost);
    }
}
