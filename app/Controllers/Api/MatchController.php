<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Repositories\MatchRepository;
use App\Services\MatchPayload;
use App\Services\MatchStats;
use InvalidArgumentException;

final class MatchController extends ApiController
{
    /**
     * Id zápasů, které už server má. Aplikace podle nich ví, co ještě poslat.
     *
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $this->json(['ok' => true, 'ids' => (new MatchRepository(db()))->clientIds((int) $user['id'])]);
    }

    /** @param array<string, string> $params */
    public function store(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $clientId = $this->clientId($params);
        if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 4_194_304) {
            $this->json(['ok' => false, 'message' => 'Zápas je moc velký.'], 413);
        }

        try {
            $match = MatchPayload::parse($request->all());
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $matches = new MatchRepository(db());
        if ($match['status'] === 'abandoned') {
            $matches->delete((int) $user['id'], $clientId);
            $this->json(['ok' => true]);
        }

        $publicId = $matches->save((int) $user['id'], $clientId, $match, MatchStats::analyze($match));
        $this->json(['ok' => true, 'id' => $publicId]);
    }

    /** @param array<string, string> $params */
    public function destroy(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        (new MatchRepository(db()))->delete((int) $user['id'], $this->clientId($params));
        $this->json(['ok' => true]);
    }

    /** @param array<string, string> $params */
    private function clientId(array $params): string
    {
        $clientId = strtolower((string) ($params['id'] ?? ''));
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $clientId)) {
            $this->json(['ok' => false, 'message' => 'Neplatné id zápasu.'], 422);
        }

        return $clientId;
    }
}
