<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use InvalidArgumentException;

/** Žádosti o přátelství mezi účty. Kamarádem se hráč stane až po přijetí. */
final class FriendRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * @return array{friends: list<array<string, mixed>>, incoming: list<array<string, mixed>>, outgoing: list<array<string, mixed>>}
     */
    public function book(int $userId): array
    {
        $friends = [];
        $incoming = [];
        $outgoing = [];
        foreach ($this->rows($userId) as $row) {
            if ($row['status'] === 'declined') {
                continue;
            }
            $item = $this->present($row);
            if ($row['status'] === 'accepted') {
                $friends[] = $item;
            } elseif ((int) $row['requester_id'] === $userId) {
                $outgoing[] = $item;
            } else {
                $incoming[] = $item;
            }
        }

        return ['friends' => $friends, 'incoming' => $incoming, 'outgoing' => $outgoing];
    }

    /** Přijatý kamarád z pohledu přihlášeného. Jiný stav nebo cizí řádek vyhodí výjimku. */
    public function accepted(int $userId, int $friendshipId): array
    {
        if ($friendshipId <= 0) {
            throw new InvalidArgumentException('Kamarád neexistuje.');
        }

        return $this->acceptedRow($this->rows($userId, $friendshipId));
    }

    /** Stejné jako accepted(), ale v odkazu je veřejné id kamaráda, ne id přátelství. */
    public function acceptedByPublicId(int $userId, string $publicId): array
    {
        $publicId = strtolower($publicId);
        if (!preg_match('/^[0-9a-f]{24}$/', $publicId)) {
            throw new InvalidArgumentException('Kamarád neexistuje.');
        }

        return $this->acceptedRow($this->rows($userId, null, $publicId));
    }

    /**
     * @param list<int> $ids
     * @return array<int, string> id druhého účtu => none|outgoing|incoming|accepted|declined
     */
    public function relations(int $userId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $ids),
            static fn (int $id): bool => $id > 0 && $id !== $userId
        )));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $rows = $this->db->fetchAll(
            "SELECT user_low, user_high, requester_id, status FROM friendships
             WHERE (user_low = ? AND user_high IN ({$placeholders}))
                OR (user_high = ? AND user_low IN ({$placeholders}))",
            array_merge([$userId], $ids, [$userId], $ids)
        );
        $map = [];
        foreach ($rows as $row) {
            $other = (int) $row['user_low'] === $userId ? (int) $row['user_high'] : (int) $row['user_low'];
            $map[$other] = self::relation($userId, (int) $row['requester_id'], (string) $row['status']);
        }

        return $map;
    }

    /**
     * Pošle žádost. Když už žádost poslal druhý hráč, tímhle se přijme.
     *
     * @return array{friendship: array<string, mixed>, relation: string}
     */
    public function request(int $userId, int $otherId): array
    {
        if ($otherId <= 0 || $userId === $otherId) {
            throw new InvalidArgumentException('Sám sebe přidat nemůžeš.');
        }
        $other = (new UserRepository($this->db))->findById($otherId);
        if ($other === null || $other['status'] !== 'active') {
            throw new InvalidArgumentException('Hráč neexistuje.');
        }

        [$low, $high] = $userId < $otherId ? [$userId, $otherId] : [$otherId, $userId];
        $row = $this->db->fetch('SELECT * FROM friendships WHERE user_low = ? AND user_high = ?', [$low, $high]);
        if ($row === null) {
            $id = $this->db->insert('friendships', [
                'user_low' => $low,
                'user_high' => $high,
                'requester_id' => $userId,
                'status' => 'pending',
            ]);

            return $this->outcome($userId, $id, 'outgoing');
        }

        $id = (int) $row['id'];
        $status = (string) $row['status'];
        $requester = (int) $row['requester_id'];
        if ($status === 'accepted') {
            return $this->outcome($userId, $id, 'accepted');
        }
        if ($status === 'pending' && $requester === $userId) {
            return $this->outcome($userId, $id, 'outgoing');
        }
        if ($status === 'pending' || ($status === 'declined' && $requester !== $userId)) {
            $this->db->update('friendships', ['status' => 'accepted'], 'id = :id', ['id' => $id]);

            return $this->outcome($userId, $id, 'accepted');
        }

        $this->db->update('friendships', [
            'requester_id' => $userId,
            'status' => 'pending',
        ], 'id = :id', ['id' => $id]);

        return $this->outcome($userId, $id, 'outgoing');
    }

    /** @return array<string, mixed> */
    public function accept(int $userId, int $id): array
    {
        $row = $this->requirePendingForAddressee($userId, $id, 'Tuhle žádost nemůžeš přijmout.');
        $this->db->update('friendships', ['status' => 'accepted'], 'id = :id', ['id' => (int) $row['id']]);

        return $this->present($this->requireRow($userId, (int) $row['id']));
    }

    /** @return array<string, mixed> */
    public function decline(int $userId, int $id): array
    {
        $row = $this->requirePendingForAddressee($userId, $id, 'Tuhle žádost nemůžeš odmítnout.');
        $this->db->update('friendships', ['status' => 'declined'], 'id = :id', ['id' => (int) $row['id']]);

        return $this->present($this->requireRow($userId, (int) $row['id']));
    }

    public function cancel(int $userId, int $id): void
    {
        $row = $this->party($userId, $id);
        if ($row === null) {
            throw new InvalidArgumentException('Žádost neexistuje.');
        }
        if ($row['status'] !== 'pending' || (int) $row['requester_id'] !== $userId) {
            throw new InvalidArgumentException('Tuhle žádost nemůžeš zrušit.');
        }
        $this->db->query('DELETE FROM friendships WHERE id = ?', [(int) $row['id']]);
    }

    public function remove(int $userId, int $id): void
    {
        $row = $this->party($userId, $id);
        if ($row === null) {
            throw new InvalidArgumentException('Kamarád neexistuje.');
        }
        if ($row['status'] !== 'accepted') {
            throw new InvalidArgumentException('Odebrat jde jen přijaté přátelství.');
        }
        $this->db->query('DELETE FROM friendships WHERE id = ?', [(int) $row['id']]);
    }

    /** @return array{friendship: array<string, mixed>, relation: string} */
    private function outcome(int $userId, int $id, string $relation): array
    {
        return [
            'friendship' => $this->present($this->requireRow($userId, $id)),
            'relation' => $relation,
        ];
    }

    /** @return array<string, mixed> */
    private function requirePendingForAddressee(int $userId, int $id, string $message): array
    {
        $row = $this->party($userId, $id);
        if ($row === null) {
            throw new InvalidArgumentException('Žádost neexistuje.');
        }
        if ($row['status'] !== 'pending' || (int) $row['requester_id'] === $userId) {
            throw new InvalidArgumentException($message);
        }

        return $row;
    }

    /** @return array<string, mixed>|null */
    private function party(int $userId, int $id): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM friendships WHERE id = ? AND (user_low = ? OR user_high = ?)',
            [$id, $userId, $userId]
        );
    }

    /** @return array<string, mixed> */
    private function requireRow(int $userId, int $id): array
    {
        foreach ($this->rows($userId, $id) as $row) {
            return $row;
        }

        throw new InvalidArgumentException('Žádost neexistuje.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(int $userId, ?int $id = null, ?string $publicId = null): array
    {
        $sql = 'SELECT f.id, f.user_low, f.user_high, f.requester_id, f.status,
                       u.id AS other_id, u.public_id, u.username, u.first_name, u.last_name, u.avatar_path, u.profile_revision, u.visibility
                FROM friendships f
                JOIN users u ON u.id = IF(f.user_low = ?, f.user_high, f.user_low)
                WHERE (f.user_low = ? OR f.user_high = ?) AND u.status = \'active\'';
        $params = [$userId, $userId, $userId];
        if ($id !== null) {
            $sql .= ' AND f.id = ?';
            $params[] = $id;
        }
        if ($publicId !== null) {
            $sql .= ' AND u.public_id = ?';
            $params[] = $publicId;
        }
        $sql .= ' ORDER BY f.updated_at DESC, u.username';

        return $this->db->fetchAll($sql, $params);
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function present(array $row): array
    {
        $user = UserRepository::toDirectory([
            'id' => $row['other_id'],
            'public_id' => $row['public_id'],
            'username' => $row['username'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'avatar_path' => $row['avatar_path'],
            'profile_revision' => $row['profile_revision'],
        ]);
        $user['private'] = (string) ($row['visibility'] ?? 'public') === 'private';

        return [
            'id' => (int) $row['id'],
            'status' => (string) $row['status'],
            'user' => $user,
        ];
    }

    /** @param list<array<string, mixed>> $rows @return array<string, mixed> */
    private function acceptedRow(array $rows): array
    {
        foreach ($rows as $row) {
            if ($row['status'] !== 'accepted') {
                throw new InvalidArgumentException('Kamarádi ještě nejste.');
            }

            return $this->present($row);
        }

        throw new InvalidArgumentException('Kamarád neexistuje.');
    }

    private static function relation(int $userId, int $requesterId, string $status): string
    {
        return match ($status) {
            'accepted' => 'accepted',
            'declined' => 'declined',
            'pending' => $requesterId === $userId ? 'outgoing' : 'incoming',
            default => 'none',
        };
    }
}
