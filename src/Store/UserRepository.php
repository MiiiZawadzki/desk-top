<?php

declare(strict_types=1);

namespace App\Store;

use App\Domain\User;

interface UserRepository
{
    public function findByEmail(string $email): ?User;

    public function add(User $user): User;

    public function count(): int;
}
