<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Auth\AuthService;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;
use Throwable;

final readonly class AuthController
{
    public function __construct(
        private AuthService $auth,
        private string $csrf,
    ) {
    }

    /**
     * @return Response
     */
    public function showLogin(): Response
    {
        return Response::html(View::login($this->csrf));
    }

    /**
     * @param  Request  $request
     * @return Response
     */
    public function login(Request $request): Response
    {
        $body = $request->jsonBody();
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        try {
            $user = $this->auth->attempt($email, $password);
            if ($user === null) {
                return Response::json(['error' => 'invalid credentials'], 401);
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user->id;
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        } catch (Throwable) {
            $this->destroySession();


            return Response::json(['error' => 'server error'], 500);
        }

        return Response::json(['ok' => true]);
    }

    /**
     * @return Response
     */
    public function logout(): Response
    {
        $this->destroySession();

        return Response::json(['ok' => true]);
    }

    /**
     * @return void
     */
    private function destroySession(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
