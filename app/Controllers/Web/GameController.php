<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Repositories\MatchRepository;

final class GameController
{
    /** @param array<string, string> $params */
    public function destroy(Request $request, array $params = []): void
    {
        $user = auth()->user();
        if ($user === null) {
            $this->json(['ok' => false, 'message' => 'Přihlášení vypršelo. Přihlas se znovu.'], 401);
        }
        if (!auth()->verifyCsrf($request->header('X-CSRF-Token'))) {
            $this->json(['ok' => false, 'message' => 'Platnost stránky vypršela. Obnov ji a zkus to znovu.'], 419);
        }

        $deleted = (new MatchRepository(db()))->deleteByPublicId((int) $user['id'], strtolower((string) ($params['id'] ?? '')));
        if (!$deleted) {
            $this->json(['ok' => false, 'message' => 'Zápas nenalezen.'], 404);
        }
        $this->json(['ok' => true]);
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
