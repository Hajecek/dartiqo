<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Repositories\MatchRepository;
use App\Services\LiveMatchPayload;
use InvalidArgumentException;

final class LiveMatchController extends ApiController
{
    /** @param array<string, string> $params */
    public function update(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $clientId = strtolower((string) ($params['id'] ?? ''));
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $clientId)) {
            $this->json(['ok' => false, 'message' => 'Neplatné id zápasu.'], 422);
        }
        if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 400_000) {
            $this->json(['ok' => false, 'message' => 'Stav zápasu je moc velký.'], 413);
        }

        try {
            $match = LiveMatchPayload::parse($request->all());
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        (new MatchRepository(db()))->saveLive(
            (int) $user['id'],
            $clientId,
            $match['status'],
            $match['seq'],
            $match['state'],
            $match['startedAt']
        );
        $this->json(['ok' => true]);
    }
}
