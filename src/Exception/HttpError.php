<?php

declare(strict_types=1);

namespace App\Exception;

class HttpError extends \RuntimeException
{
    public function __construct(string $message, private readonly int $status)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
