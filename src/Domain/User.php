<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class User
{
    public function __construct(
        public ?string $id,
        public string $email,
        public string $passwordHash,
    ) {
    }
}
