<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Repositories\FriendRepository;
use App\Repositories\OnlineMatchRepository;
use App\Services\OnlineMatchException;
use App\Services\OnlineX01;
use InvalidArgumentException;

/** Online zápas. Skóre mění jen šipka hráče, který je zrovna na tahu. */
final class OnlineMatchController extends ApiController
{
    /** @param array<string, string> $params */
    public function store(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $this->guardSize(16_384);
        try {
            $match = (new OnlineMatchRepository(db()))->create($user, OnlineX01::lobby($request->all()));
        } catch (OnlineMatchException $e) {
            $this->fail($e);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
        $this->json(['ok' => true, 'match' => $match]);
    }

    /** @param array<string, string> $params */
    public function update(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $this->guardSize(16_384);
        try {
            $match = (new OnlineMatchRepository(db()))->update($user, $this->publicId($params), OnlineX01::lobby($request->all()));
        } catch (OnlineMatchException $e) {
            $this->fail($e);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
        $this->json($this->withLobby($user, $match));
    }

    /** @param array<string, string> $params */
    public function lobbies(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $kind = $request->input('kind');
        $kind = in_array($kind, ['classic', 'ranked'], true) ? $kind : null;
        $repository = new OnlineMatchRepository(db());
        $this->json([
            'ok' => true,
            'lobbies' => $repository->lobbies((int) $user['id'], $kind),
            'rating' => $repository->rating((int) $user['id']),
        ]);
    }

    /** @param array<string, string> $params */
    public function joinPublic(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        try {
            $match = (new OnlineMatchRepository(db()))->joinPublic($user, $this->publicId($params));
        } catch (OnlineMatchException $e) {
            $this->fail($e);
        }
        $this->json(['ok' => true, 'match' => $match]);
    }

    /** @param array<string, string> $params */
    public function join(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $this->guardSize(16_384);
        $body = $request->all();
        $password = $body['password'] ?? '';
        if (!is_string($password)) {
            $this->json(['ok' => false, 'message' => 'Neplatné heslo.'], 422);
        }
        try {
            $match = (new OnlineMatchRepository(db()))->join($user, OnlineX01::code((string) ($body['code'] ?? '')), $password);
        } catch (OnlineMatchException $e) {
            $this->fail($e);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
        $this->json(['ok' => true, 'match' => $match]);
    }

    /** @param array<string, string> $params */
    public function current(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $match = (new OnlineMatchRepository(db()))->current((int) $user['id']);
        $this->json($this->withLobby($user, $match));
    }

    /** @param array<string, string> $params */
    public function show(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        try {
            $match = (new OnlineMatchRepository(db()))->show((int) $user['id'], $this->publicId($params));
        } catch (OnlineMatchException $e) {
            $this->fail($e);
        }
        $this->json($this->withLobby($user, $match));
    }

    /** @param array<string, string> $params */
    public function ready(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $this->guardSize(16_384);
        try {
            $match = (new OnlineMatchRepository(db()))->ready((int) $user['id'], $this->publicId($params), OnlineX01::ready($request->all()));
        } catch (OnlineMatchException $e) {
            $this->fail($e);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
        $this->json(['ok' => true, 'match' => $match]);
    }

    /** @param array<string, string> $params */
    public function start(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        try {
            $match = (new OnlineMatchRepository(db()))->start($user, $this->publicId($params));
        } catch (OnlineMatchException $e) {
            $this->fail($e);
        }
        $this->json(['ok' => true, 'match' => $match]);
    }

    /** @param array<string, string> $params */
    public function dart(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $this->guardSize(65_536);
        try {
            $match = (new OnlineMatchRepository(db()))->throwDart($user, $this->publicId($params), OnlineX01::dart($request->all()));
        } catch (OnlineMatchException $e) {
            $this->fail($e);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
        $this->json(['ok' => true, 'match' => $match]);
    }

    /** @param array<string, string> $params */
    public function abandon(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        try {
            $match = (new OnlineMatchRepository(db()))->abandon($user, $this->publicId($params));
        } catch (OnlineMatchException $e) {
            $this->fail($e);
        }
        $this->json(['ok' => true, 'match' => $match]);
    }

    /** @param array<string, string> $params */
    public function invites(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $this->json(['ok' => true, 'invites' => (new OnlineMatchRepository(db()))->incoming((int) $user['id'])]);
    }

    /** @param array<string, string> $params */
    public function invite(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $this->guardSize(16_384);
        $body = $request->all();
        $raw = $body['user'] ?? $body['userId'] ?? '';
        try {
            if (is_string($raw) && preg_match('/^[0-9a-f]{24}$/', strtolower($raw)) === 1) {
                $friend = (new FriendRepository(db()))->acceptedByPublicId((int) $user['id'], strtolower($raw));
                $friendId = (int) $friend['user']['id'];
            } else {
                $friendId = (int) $raw;
            }
            $match = (new OnlineMatchRepository(db()))->invite($user, $this->publicId($params), $friendId);
        } catch (OnlineMatchException $e) {
            $this->fail($e);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
        $this->json(['ok' => true, 'match' => $match]);
    }

    /** @param array<string, string> $params */
    public function acceptInvite(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        try {
            $match = (new OnlineMatchRepository(db()))->acceptInvite($user, $this->inviteId($params));
        } catch (OnlineMatchException $e) {
            $this->fail($e);
        }
        $this->json(['ok' => true, 'match' => $match]);
    }

    /** @param array<string, string> $params */
    public function declineInvite(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        (new OnlineMatchRepository(db()))->declineInvite((int) $user['id'], $this->inviteId($params));
        $this->json(['ok' => true]);
    }

    /** @param array<string, string> $params */
    private function publicId(array $params): string
    {
        $id = strtolower((string) ($params['id'] ?? ''));
        if (preg_match('/^[0-9a-f]{24}$/', $id) !== 1) {
            $this->json(['ok' => false, 'message' => 'Neplatné id zápasu.'], 422);
        }

        return $id;
    }

    /** @param array<string, string> $params */
    private function inviteId(array $params): int
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id < 1) {
            $this->json(['ok' => false, 'message' => 'Pozvánka neexistuje.'], 404);
        }

        return $id;
    }

    /**
     * V lobby přiloží pozvánky, ať telefon ví, koho už hostitel zavolal.
     *
     * @param array<string, mixed> $user
     * @param array<string, mixed>|null $match
     * @return array<string, mixed>
     */
    private function withLobby(array $user, ?array $match): array
    {
        $payload = ['ok' => true, 'match' => $match];
        if (is_array($match) && ($match['status'] ?? '') === 'lobby') {
            $payload['lobbyInvites'] = (new OnlineMatchRepository(db()))->lobbyInvites((string) $match['id'], (int) $user['id']);
        }

        return $payload;
    }

    private function guardSize(int $limit): void
    {
        if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $limit) {
            $this->json(['ok' => false, 'message' => 'Požadavek je moc velký.'], 413);
        }
    }

    private function fail(OnlineMatchException $e): never
    {
        $payload = ['ok' => false, 'message' => $e->getMessage()];
        if ($e->error !== null) {
            $payload['code'] = $e->error;
        }
        if ($e->match !== null) {
            $payload['match'] = $e->match;
        }
        $this->json($payload, $e->status);
    }
}
