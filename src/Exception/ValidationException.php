<?php

declare(strict_types=1);

namespace App\Exception;

final class ValidationException extends HttpError
{
    public function __construct(string $message = 'invalid request')
    {
        parent::__construct($message, 422);
    }
}
