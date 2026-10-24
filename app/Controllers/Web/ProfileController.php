<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Repositories\UserRepository;
use App\Services\ProfileService;

final class ProfileController
{
    private const STREAM_SECONDS = 25;
    private const POLL_MICROSECONDS = 800_000;
    private const HEARTBEAT_SECONDS = 10;

    /** @param array<string, string> $params */
    public function update(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $user = auth()->user();
        if ($user === null) {
            $this->json(['ok' => false, 'message' => 'Nejsi přihlášený.'], 401);
        }

        $body = $request->all();
        $input = [
            'first' => $body['first'] ?? null,
            'last' => $body['last'] ?? null,
            'username' => $body['username'] ?? null,
            'email' => $body['email'] ?? null,
        ];
        if (array_key_exists('photo', $body)) {
            $input['photo'] = $body['photo'];
        }
        foreach (['first', 'last', 'username', 'email'] as $key) {
            if ($input[$key] === null) {
                unset($input[$key]);
            } else {
                $input[$key] = (string) $input[$key];
            }
        }

        $result = (new ProfileService(new UserRepository(db())))->update($user, $input);
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

        auth()->refreshUser();
        $this->json(['ok' => true, 'user' => $result['user'], 'csrf' => auth()->csrfToken()]);
    }

    /** Soukromý účet schová kamarádům statistiky. Veřejný je nechá viditelné. @param array<string, string> $params */
    public function visibility(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $user = auth()->user();
        if ($user === null) {
            $this->json(['ok' => false, 'message' => 'Nejsi přihlášený.'], 401);
        }

        $visibility = $request->input('visibility');
        if ($visibility !== 'public' && $visibility !== 'private') {
            $this->json(['ok' => false, 'message' => 'Vyber veřejný, nebo soukromý účet.'], 422);
        }

        $users = new UserRepository(db());
        if ((string) ($user['visibility'] ?? 'public') !== $visibility) {
            $users->setVisibility((int) $user['id'], $visibility);
            auth()->refreshUser();
        }
        $fresh = $users->findById((int) $user['id']);
        if ($fresh === null) {
            $this->json(['ok' => false, 'message' => 'Účet se nepodařilo načíst.'], 500);
        }

        $this->json(['ok' => true, 'user' => UserRepository::toPublic($fresh), 'csrf' => auth()->csrfToken()]);
    }

    /**
     * SSE: web dostane nový profil hned po změně z aplikace (nebo jiného okna).
     *
     * @param array<string, string> $params
     */
    public function stream(Request $request, array $params = []): void
    {
        $user = auth()->user();
        if ($user === null) {
            http_response_code(401);
            exit;
        }
        session_write_close();
        set_time_limit(self::STREAM_SECONDS + 15);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Accel-Buffering: no');

        $users = new UserRepository(db());
        $lastRevision = $request->header('Last-Event-ID');
        $deadline = time() + self::STREAM_SECONDS;
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
            } elseif (time() - $lastWrite >= self::HEARTBEAT_SECONDS) {
                echo ": ping\n\n";
                $lastWrite = time();
                flush();
            }
            usleep(self::POLL_MICROSECONDS);
        }
        exit;
    }

    private function requireCsrf(Request $request): void
    {
        if (!auth()->verifyCsrf($request->header('X-CSRF-Token'))) {
            $this->json(['ok' => false, 'message' => 'Platnost formuláře vypršela. Obnov stránku a zkus to znovu.'], 419);
        }
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
