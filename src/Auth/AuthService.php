<?php

declare(strict_types=1);

namespace App\Auth;

use App\Domain\User;
use App\Store\UserRepository;

final readonly class AuthService
{
    public function __construct(private UserRepository $users)
    {
    }

    /**
     * @param  string  $email
     * @param  string  $password
     * @return User|null
     */
    public function attempt(string $email, string $password): ?User
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return null;
        }

        return password_verify($password, $user->passwordHash) ? $user : null;
    }

    /**
     * @param  string  $password
     * @return string
     */
    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
