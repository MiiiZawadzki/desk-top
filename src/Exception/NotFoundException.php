<?php

declare(strict_types=1);

namespace App\Exception;

final class NotFoundException extends HttpError
{
    public function __construct(string $message = 'not found')
    {
        parent::__construct($message, 404);
    }
}
