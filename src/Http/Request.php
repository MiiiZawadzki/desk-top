<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query,
        private readonly array $headers,
        private readonly string $rawBody,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with((string)$key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr((string)$key, 5)));
                $headers[$name] = (string)$value;
            }
        }

        return new self($method, $path, $_GET, $headers, file_get_contents('php://input') ?: '');
    }

    public function query(string $key, string $default = ''): string
    {
        return isset($this->query[$key]) ? (string)$this->query[$key] : $default;
    }

    public function queryAll(): array
    {
        return $this->query;
    }

    public function header(string $name): string
    {
        return $this->headers[strtolower($name)] ?? '';
    }

    public function csrfToken(): string
    {
        return $this->header('x-csrf-token');
    }

    public function origin(): string
    {
        return $this->header('origin');
    }

    public function referer(): string
    {
        return $this->header('referer');
    }

    public function host(): string
    {
        return $this->header('host');
    }

    public function jsonBody(): array
    {
        $data = json_decode($this->rawBody, true);
        return is_array($data) ? $data : [];
    }
}
