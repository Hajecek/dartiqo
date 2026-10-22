<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Repositories\UserRepository;

final class AuthController extends ApiController
{
    /** @param array<string, string> $params */
    public function login(Request $request, array $params = []): void
    {
        $identity = trim($request->input('identity'));
        $password = $request->input('password');
        if ($identity === '' || $password === '') {
            $this->json(['ok' => false, 'message' => 'Vyplň přihlašovací údaje.'], 422);
        }
        if (str_contains($identity, '@')) {
            $identity = mb_strtolower($identity);
        }

        $user = auth()->verifyCredentials($identity, $password);
        if ($user === null) {
            $this->json(['ok' => false, 'message' => 'Nesprávný e-mail, uživatelské jméno nebo heslo.'], 401);
        }

        $token = $this->tokens->create((int) $user['id'], trim($request->input('device')));
        $this->json(['ok' => true, 'token' => $token, 'user' => UserRepository::toPublic($user)]);
    }

    /** @param array<string, string> $params */
    public function logout(Request $request, array $params = []): void
    {
        $this->tokens->revoke($request->bearerToken());
        $this->json(['ok' => true]);
    }

    /** @param array<string, string> $params */
    public function me(Request $request, array $params = []): void
    {
        $this->json(['ok' => true, 'user' => UserRepository::toPublic($this->requireUser($request))]);
    }
}
