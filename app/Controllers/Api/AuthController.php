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

    /**
     * Přihlášení přes Apple / Google. Aplikace posílá identity token poskytovatele.
     * Ověření tokenu (JWKS, aud = bundle ID / client ID) a propojení s účtem zatím chybí.
     *
     * @param array<string, string> $params
     */
    public function social(Request $request, array $params = []): void
    {
        $names = ['apple' => 'Apple', 'google' => 'Google'];
        $provider = $params['provider'] ?? '';
        if (!isset($names[$provider])) {
            $this->json(['ok' => false, 'message' => 'Neznámý způsob přihlášení.'], 404);
        }
        if (trim($request->input('idToken')) === '') {
            $this->json(['ok' => false, 'message' => 'Chybí token od poskytovatele.'], 422);
        }

        $this->json(['ok' => false, 'message' => 'Přihlášení přes ' . $names[$provider] . ' zatím není na serveru zapnuté. Použij e-mail a heslo.'], 501);
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
