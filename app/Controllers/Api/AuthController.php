<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Repositories\UserRepository;
use App\Services\ProfileService;

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

    /** @param array<string, string> $params */
    public function update(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $body = $request->all();
        $users = new UserRepository(db());
        if (array_key_exists('visibility', $body)) {
            $visibility = is_scalar($body['visibility']) ? (string) $body['visibility'] : '';
            if ($visibility !== 'public' && $visibility !== 'private') {
                $this->json(['ok' => false, 'message' => 'Vyber veřejný, nebo soukromý účet.'], 422);
            }
            if ((string) ($user['visibility'] ?? 'public') !== $visibility) {
                $users->setVisibility((int) $user['id'], $visibility);
                $user = $users->findById((int) $user['id']) ?? $user;
            }
        }
        $input = [];
        foreach (['first', 'last', 'username', 'email'] as $key) {
            if (array_key_exists($key, $body) && $body[$key] !== null) {
                $input[$key] = (string) $body[$key];
            }
        }
        if (array_key_exists('photo', $body)) {
            $input['photo'] = $body['photo'];
        }
        if ($input === [] && !array_key_exists('photo', $body)) {
            $fresh = $users->findById((int) $user['id']) ?? $user;
            $this->json(['ok' => true, 'user' => UserRepository::toPublic($fresh)]);
        }

        $result = (new ProfileService($users))->update($user, $input);
        if (!$result['ok']) {
            $payload = ['ok' => false];
            if (isset($result['errors'])) {
                $payload['errors'] = $result['errors'];
            }
            if (isset($result['message'])) {
                $payload['message'] = $result['message'];
            }
            $this->json($payload, $result['status']);
        }

        $this->json(['ok' => true, 'user' => $result['user']]);
    }

    /**
     * SSE pro aplikaci: Authorization Bearer. EventSource v prohlížeči používá cookie endpoint.
     *
     * @param array<string, string> $params
     */
    public function stream(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        set_time_limit(40);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Accel-Buffering: no');

        $users = new UserRepository(db());
        $lastRevision = $request->header('Last-Event-ID');
        $deadline = time() + 25;
        $lastWrite = time();
        echo "retry: 1500\n\n";
        flush();

        while (time() < $deadline && !connection_aborted()) {
            $fresh = $users->findById((int) $user['id']);
            if ($fresh === null) {
                echo "event: gone\ndata: {}\n\n";
                flush();
                exit;
            }
            $revision = (string) (int) ($fresh['profile_revision'] ?? 1);
            if ($revision !== $lastRevision) {
                echo 'id: ' . $revision . "\n";
                echo 'data: ' . json_encode(
                    ['user' => UserRepository::toPublic($fresh)],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
                ) . "\n\n";
                $lastRevision = $revision;
                $lastWrite = time();
                flush();
            } elseif (time() - $lastWrite >= 10) {
                echo ": ping\n\n";
                $lastWrite = time();
                flush();
            }
            usleep(800_000);
        }
        exit;
    }
}
