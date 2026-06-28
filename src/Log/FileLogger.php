<?php

declare(strict_types=1);

namespace App\Log;

final readonly class FileLogger implements Logger
{
    public function __construct(private string $file)
    {
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $line = sprintf(
            "%s %s %s %s\n",
            date('c'),
            $level,
            $message,
            $context === [] ? '{}' : (json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}'),
        );
        @file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
    }
}
