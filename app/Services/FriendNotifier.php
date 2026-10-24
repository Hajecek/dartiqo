<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/** Push po žádosti o přátelství. Stejné z aplikace i z webu, jen když se stav opravdu změnil. */
final class FriendNotifier
{
    private readonly PushService $push;

    public function __construct(Database $db)
    {
        $this->push = new PushService($db);
    }

    /**
     * @param array<string, mixed> $actor
     * @param array{friendship?: array<string, mixed>, relation?: string, changed?: bool} $result
     */
    public function afterRequest(array $actor, array $result): void
    {
        if (($result['changed'] ?? false) !== true) {
            return;
        }
        $relation = (string) ($result['relation'] ?? '');
        $friendship = is_array($result['friendship'] ?? null) ? $result['friendship'] : [];
        if ($relation === 'accepted') {
            $this->accepted($actor, $friendship);

            return;
        }
        if ($relation === 'outgoing') {
            $this->requested($actor, $friendship);
        }
    }

    /** @param array<string, mixed> $actor @param array<string, mixed> $friendship */
    public function accepted(array $actor, array $friendship): void
    {
        $otherId = $this->otherId($actor, $friendship);
        if ($otherId === null) {
            return;
        }
        $this->push->send(
            $otherId,
            'Nový kamarád',
            $this->name($actor) . ' přijal tvoji žádost o přátelství.',
            'friend.accepted'
        );
    }

    /** @param array<string, mixed> $actor @param array<string, mixed> $friendship */
    private function requested(array $actor, array $friendship): void
    {
        $otherId = $this->otherId($actor, $friendship);
        if ($otherId === null) {
            return;
        }
        $this->push->send(
            $otherId,
            'Nová žádost',
            $this->name($actor) . ' ti posílá žádost o přátelství.',
            'friend.request'
        );
    }

    /** @param array<string, mixed> $actor @param array<string, mixed> $friendship */
    private function otherId(array $actor, array $friendship): ?int
    {
        $user = $friendship['user'] ?? null;
        $otherId = is_array($user) ? (int) ($user['id'] ?? 0) : 0;
        $actorId = (int) ($actor['id'] ?? 0);
        if ($otherId < 1 || $otherId === $actorId) {
            return null;
        }

        return $otherId;
    }

    /** @param array<string, mixed> $actor */
    private function name(array $actor): string
    {
        $name = trim((string) ($actor['first_name'] ?? '') . ' ' . (string) ($actor['last_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }
        $username = trim((string) ($actor['username'] ?? ''));

        return $username !== '' ? $username : 'Někdo';
    }
}
