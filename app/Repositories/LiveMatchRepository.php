<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class LiveMatchRepository
{
    /** Rozehraný zápas bez aktualizace déle než tohle už web neukazuje (zavřená aplikace). */
    private const STALE_MINUTES = 45;
    /** Jak dlouho po konci zůstane vidět výsledek. */
    private const FINISHED_MINUTES = 5;

    public function __construct(private readonly Database $db)
    {
    }

    /** @param array<string, mixed> $state */
    public function save(int $userId, string $clientId, string $status, int $seq, array $state, string $startedAt): void
    {
        $now = gmdate('Y-m-d H:i:s');
        // seq se nastavuje až poslední, ostatní sloupce ho porovnávají s původní hodnotou
        $this->db->query(
            'INSERT INTO live_matches (user_id, client_id, status, seq, state, started_at, updated_at, finished_at)
             VALUES (:user_id, :client_id, :status, :seq, :state, :started_at, :updated_at, :finished_at)
             ON DUPLICATE KEY UPDATE
                state = IF(VALUES(seq) > seq, VALUES(state), state),
                status = IF(VALUES(seq) > seq, VALUES(status), status),
                finished_at = IF(VALUES(seq) > seq, VALUES(finished_at), finished_at),
                updated_at = IF(VALUES(seq) > seq, VALUES(updated_at), updated_at),
                seq = GREATEST(seq, VALUES(seq))',
            [
                'user_id' => $userId,
                'client_id' => $clientId,
                'status' => $status,
                'seq' => $seq,
                'state' => json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'started_at' => $startedAt,
                'updated_at' => $now,
                'finished_at' => $status === 'live' ? null : $now,
            ]
        );
    }

    /** @return array<string, mixed>|null Zápas, který má web právě ukazovat. */
    public function current(int $userId): ?array
    {
        $row = $this->db->fetch(
            'SELECT * FROM live_matches
             WHERE user_id = :user_id AND (
                (status = \'live\' AND updated_at > UTC_TIMESTAMP() - INTERVAL ' . self::STALE_MINUTES . ' MINUTE)
                OR (status = \'finished\' AND finished_at > UTC_TIMESTAMP() - INTERVAL ' . self::FINISHED_MINUTES . ' MINUTE)
             )
             ORDER BY updated_at DESC
             LIMIT 1',
            ['user_id' => $userId]
        );

        return $row === null ? null : self::toPublic($row);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function toPublic(array $row): array
    {
        $state = json_decode((string) $row['state'], true);

        return [
            'id' => $row['client_id'],
            'status' => $row['status'],
            'seq' => (int) $row['seq'],
            'startedAt' => gmdate('c', (int) strtotime($row['started_at'] . ' UTC')),
            'updatedAt' => gmdate('c', (int) strtotime($row['updated_at'] . ' UTC')),
        ] + (is_array($state) ? $state : []);
    }
}
