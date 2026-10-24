<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Repositories\FriendRepository;
use App\Services\FriendDossier;
use InvalidArgumentException;

final class FriendController extends ApiController
{
    /** @param array<string, string> $params */
    public function index(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $book = (new FriendDossier(db()))->withSummaries((int) $user['id'], (new FriendRepository(db()))->book((int) $user['id']));
        $this->json(['ok' => true] + $book);
    }

    /** @param array<string, string> $params */
    public function show(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $repo = new FriendRepository(db());
        try {
            $friend = $repo->accepted((int) $user['id'], (int) ($params['id'] ?? 0));
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 404);
        }

        $this->json([
            'ok' => true,
            'record' => (new FriendDossier(db()))->detail((int) $user['id'], (int) $friend['user']['id']),
        ]);
    }

    /** @param array<string, string> $params */
    public function store(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $otherId = (int) $request->input('userId');
        if ($otherId <= 0) {
            $this->json(['ok' => false, 'message' => 'Vyber hráče.'], 422);
        }

        try {
            $result = (new FriendRepository(db()))->request((int) $user['id'], $otherId);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $this->json(['ok' => true, 'friendship' => $result['friendship'], 'relation' => $result['relation']]);
    }

    /** @param array<string, string> $params */
    public function accept(Request $request, array $params = []): void
    {
        $this->act($request, $params, 'accept');
    }

    /** @param array<string, string> $params */
    public function decline(Request $request, array $params = []): void
    {
        $this->act($request, $params, 'decline');
    }

    /** @param array<string, string> $params */
    public function cancel(Request $request, array $params = []): void
    {
        $this->act($request, $params, 'cancel');
    }

    /** @param array<string, string> $params */
    public function destroy(Request $request, array $params = []): void
    {
        $this->act($request, $params, 'remove');
    }

    /** @param array<string, string> $params */
    private function act(Request $request, array $params, string $action): void
    {
        $user = $this->requireUser($request);
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'Žádost neexistuje.'], 404);
        }

        $repo = new FriendRepository(db());
        try {
            if ($action === 'cancel') {
                $repo->cancel((int) $user['id'], $id);
                $this->json(['ok' => true]);
            }
            if ($action === 'remove') {
                $repo->remove((int) $user['id'], $id);
                $this->json(['ok' => true]);
            }
            $friendship = $action === 'accept'
                ? $repo->accept((int) $user['id'], $id)
                : $repo->decline((int) $user['id'], $id);
        } catch (InvalidArgumentException $e) {
            $missing = str_contains($e->getMessage(), 'neexistuje');
            $this->json(['ok' => false, 'message' => $e->getMessage()], $missing ? 404 : 422);
        }

        $this->json(['ok' => true, 'friendship' => $friendship]);
    }
}
