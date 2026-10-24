<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDOException;

final class MatchRepository
{
    /** Rozehraný zápas bez aktualizace déle než tohle už web neukazuje (zavřená aplikace). */
    private const LIVE_STALE_MINUTES = 45;
    /** Jak dlouho po konci zůstane na živé obrazovce výsledek. */
    private const LIVE_FINISHED_SECONDS = 90;

    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Uloží živý snímek zápasu. Stejný client_id později přepíše dohraný zápas.
     * Starší pořadí (seq) nepřepíše novější snímek. Opuštěný nedohraný zápas smaže.
     *
     * @param array<string, mixed> $state
     */
    public function saveLive(int $userId, string $clientId, string $status, int $seq, array $state, string $startedAt): void
    {
        if ($status === 'abandoned') {
            $this->db->query(
                'DELETE FROM matches
                 WHERE user_id = :user_id AND client_id = :client_id AND seq <= :seq
                   AND JSON_EXTRACT(log, \'$.version\') IS NULL',
                ['user_id' => $userId, 'client_id' => $clientId, 'seq' => $seq]
            );

            return;
        }

        $now = gmdate('Y-m-d H:i:s');
        $this->db->transaction(function (Database $db) use ($userId, $clientId, $status, $seq, $state, $startedAt, $now): void {
            $existing = $db->fetch(
                'SELECT id, seq, log FROM matches WHERE user_id = :user AND client_id = :client FOR UPDATE',
                ['user' => $userId, 'client' => $clientId]
            );
            if ($existing === null) {
                $row = $this->liveColumns($status, $seq, $state, $now, $startedAt);
                for ($attempt = 0; ; $attempt++) {
                    try {
                        $db->insert('matches', $row + [
                            'public_id' => self::newPublicId(),
                            'user_id' => $userId,
                            'client_id' => $clientId,
                        ]);

                        return;
                    } catch (PDOException $e) {
                        if ($e->getCode() !== '23000' || $attempt >= 2) {
                            throw $e;
                        }
                        $existing = $db->fetch(
                            'SELECT id, seq, log FROM matches WHERE user_id = :user AND client_id = :client FOR UPDATE',
                            ['user' => $userId, 'client' => $clientId]
                        );
                        if ($existing !== null) {
                            break;
                        }
                    }
                }
            }

            if ((int) $existing['seq'] >= $seq) {
                return;
            }

            $previous = json_decode((string) $existing['log'], true);
            if (is_array($previous) && array_key_exists('version', $previous)) {
                $previous['board'] = $state;
                $db->update('matches', [
                    'seq' => $seq,
                    'updated_at' => $now,
                    'log' => self::json($previous),
                ], 'id = :id', ['id' => (int) $existing['id']]);

                return;
            }

            $db->update('matches', $this->liveColumns($status, $seq, $state, $now, null), 'id = :id', ['id' => (int) $existing['id']]);
        });
    }

    /** @return array<string, mixed>|null Zápas, který má web právě ukazovat živě. */
    public function currentLive(int $userId): ?array
    {
        $row = $this->db->fetch(
            'SELECT client_id, status, seq, started_at, updated_at, log FROM matches
             WHERE user_id = :user_id
               AND JSON_EXTRACT(log, \'$.board\') IS NOT NULL
               AND (
                    (status = \'live\' AND updated_at > UTC_TIMESTAMP() - INTERVAL ' . self::LIVE_STALE_MINUTES . ' MINUTE)
                    OR (status = \'finished\' AND finished_at > UTC_TIMESTAMP() - INTERVAL ' . self::LIVE_FINISHED_SECONDS . ' SECOND)
               )
             ORDER BY updated_at DESC
             LIMIT 1',
            ['user_id' => $userId]
        );
        if ($row === null) {
            return null;
        }

        $log = json_decode((string) $row['log'], true);
        $state = is_array($log['board'] ?? null) ? $log['board'] : null;
        if ($state === null) {
            return null;
        }

        return [
            'id' => $row['client_id'],
            'status' => $row['status'],
            'seq' => (int) $row['seq'],
            'startedAt' => gmdate('c', (int) strtotime($row['started_at'] . ' UTC')),
            'updatedAt' => gmdate('c', (int) strtotime($row['updated_at'] . ' UTC')),
        ] + $state;
    }

    /**
     * Uloží zápas jako jeden řádek s kompletním logem. Opakované nahrání ho přepíše
     * a veřejné id zůstane stejné. Vrací veřejné id zápasu.
     *
     * @param array<string, mixed> $match výstup MatchPayload::parse
     * @param array<string, mixed> $stats výstup MatchStats::analyze
     */
    public function save(int $userId, string $clientId, array $match, array $stats): string
    {
        $players = $this->linkAccounts($userId, $stats['players']);
        $owner = null;
        foreach ($players as $player) {
            if ($player['kind'] === 'owner') {
                $owner = $player;
                break;
            }
        }

        $log = [
            'version' => 1,
            'status' => $match['status'],
            'title' => $match['title'],
            'startedAt' => gmdate('c', $match['startedAt']),
            'finishedAt' => $match['finishedAt'] === null ? null : gmdate('c', $match['finishedAt']),
            'playSeconds' => $match['playSeconds'],
            'config' => $match['config'],
            'ghost' => $match['ghost'],
            'openingStarter' => $match['openingStarter'],
            'winner' => $match['winner'],
            'players' => $players,
            'legs' => $stats['legs'],
            'visits' => $stats['visits'],
            'app' => $match['app'],
            'raw' => $match['raw'],
        ];

        $row = [
            'status' => $match['status'],
            'mode' => $match['config']['mode'],
            'title' => $match['title'],
            'player_count' => count($players),
            'has_bot' => (int) in_array('bot', array_column($players, 'kind'), true),
            'is_solo' => (int) $stats['solo'],
            'result' => $owner['result'] ?? 'none',
            'average' => $owner['stats']['average'] ?? null,
            'darts_thrown' => array_sum(array_map(static fn (array $p): int => $p['stats']['darts'], $players)),
            'play_seconds' => $match['playSeconds'],
            'started_at' => gmdate('Y-m-d H:i:s', $match['startedAt']),
            'finished_at' => $match['finishedAt'] === null ? null : gmdate('Y-m-d H:i:s', $match['finishedAt']),
            'log' => self::json($log),
        ];

        return $this->db->transaction(function (Database $db) use ($userId, $clientId, $row): string {
            $existing = $db->fetch('SELECT id, public_id, log FROM matches WHERE user_id = :user AND client_id = :client FOR UPDATE', [
                'user' => $userId,
                'client' => $clientId,
            ]);
            if ($existing !== null) {
                $previous = json_decode((string) $existing['log'], true);
                if (is_array($previous) && is_array($previous['board'] ?? null)) {
                    $decoded = json_decode($row['log'], true, 512, JSON_THROW_ON_ERROR);
                    $decoded['board'] = $previous['board'];
                    $row['log'] = self::json($decoded);
                }
                $db->update('matches', $row, 'id = :id', ['id' => (int) $existing['id']]);

                return (string) $existing['public_id'];
            }

            for ($attempt = 0; ; $attempt++) {
                $publicId = self::newPublicId();
                try {
                    $db->insert('matches', $row + ['public_id' => $publicId, 'user_id' => $userId, 'client_id' => $clientId]);

                    return $publicId;
                } catch (PDOException $e) {
                    // 23000 = porušený unikátní klíč; při shodě public_id zkusí jiné
                    if ($e->getCode() !== '23000' || $attempt >= 2) {
                        throw $e;
                    }
                }
            }
        });
    }

    /**
     * Zápas jen pro jeho majitele. Cizí nebo neexistující zápas vrací null, takže nejde poznat, jestli existuje.
     *
     * @return array<string, mixed>|null
     */
    public function findForUser(int $userId, string $publicId): ?array
    {
        if (!preg_match('/^[0-9a-f]{24}$/', $publicId)) {
            return null;
        }
        $row = $this->db->fetch('SELECT * FROM matches WHERE public_id = :public AND user_id = :user', [
            'public' => $publicId,
            'user' => $userId,
        ]);
        if ($row === null) {
            return null;
        }
        $log = json_decode((string) $row['log'], true);
        if (!is_array($log) || !array_key_exists('version', $log)) {
            return null;
        }
        unset($log['board']);
        $row['log'] = $log;
        unset($row['id'], $row['user_id']);

        return $row;
    }

    /**
     * Dohrané zápasy účtu od nejnovějšího, s dekódovaným logem. Rozehraný zápas tu není.
     *
     * @return list<array<string, mixed>>
     */
    public function allForUser(int $userId): array
    {
        $this->deleteAbandoned($userId);
        $rows = $this->db->fetchAll(
            'SELECT public_id, status, mode, title, is_solo, result, average, play_seconds, started_at, finished_at,
                    JSON_REMOVE(log, \'$.board\') AS log
             FROM matches
             WHERE user_id = :user AND status = \'finished\' AND JSON_EXTRACT(log, \'$.version\') IS NOT NULL
             ORDER BY started_at DESC, id DESC',
            ['user' => $userId]
        );
        foreach ($rows as &$row) {
            $row['log'] = json_decode((string) $row['log'], true) ?: [];
        }

        return $rows;
    }

    /** Nedohraný zápas v historii nezůstává. */
    public function deleteAbandoned(int $userId): void
    {
        $this->db->query('DELETE FROM matches WHERE user_id = :user AND status = \'abandoned\'', ['user' => $userId]);
    }

    /**
     * Smazání nechá dohraný zápas v tabulce se stavem deleted.
     * Rozehraný zápas bez logu se tím nesmaže.
     */
    public function delete(int $userId, string $clientId): void
    {
        $this->markDeleted('user_id = :user AND client_id = :client', ['user' => $userId, 'client' => $clientId]);
    }

    /** Smazání z webu podle veřejného id. Cizí zápas nesmaže a vrací false. */
    public function deleteByPublicId(int $userId, string $publicId): bool
    {
        if (!preg_match('/^[0-9a-f]{24}$/', $publicId)) {
            return false;
        }

        return $this->markDeleted('public_id = :public AND user_id = :user', [
            'public' => $publicId,
            'user' => $userId,
        ]) > 0;
    }

    /**
     * Dokončené zápasy účtu: ty ve seznamu i ty, které uživatel smazal.
     * Smazané mají stav deleted a ze seznamu i statistik zmizí, v počtu zůstávají.
     */
    public function completedCount(int $userId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS n FROM matches
             WHERE user_id = :user AND status IN (\'finished\', \'deleted\')
               AND JSON_EXTRACT(log, \'$.version\') IS NOT NULL',
            ['user' => $userId]
        );

        return (int) ($row['n'] ?? 0);
    }

    /** Nedohraný zápas v tabulce nezůstává. Smazaný dokončený zápas se nemaže. */
    public function discard(int $userId, string $clientId): void
    {
        $this->db->query(
            'DELETE FROM matches WHERE user_id = :user AND client_id = :client AND status <> \'deleted\'',
            ['user' => $userId, 'client' => $clientId]
        );
    }

    /** @param array<string, mixed> $params */
    private function markDeleted(string $where, array $params): int
    {
        return $this->db->query(
            'UPDATE matches SET status = \'deleted\'
             WHERE ' . $where . ' AND status IN (\'finished\', \'abandoned\')
               AND JSON_EXTRACT(log, \'$.version\') IS NOT NULL',
            $params
        )->rowCount();
    }

    /**
     * Id dohraných zápasů. Rozehraný zápas tu není, aby ho aplikace po konci ještě poslala celý.
     *
     * @return list<string>
     */
    public function clientIds(int $userId): array
    {
        return array_column($this->db->fetchAll(
            'SELECT client_id FROM matches
             WHERE user_id = :user AND JSON_EXTRACT(log, \'$.version\') IS NOT NULL
             ORDER BY started_at',
            ['user' => $userId]
        ), 'client_id');
    }

    /** @param array<string, mixed> $value */
    private static function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function liveColumns(string $status, int $seq, array $state, string $now, ?string $startedAt): array
    {
        $players = array_values(array_filter(
            is_array($state['players'] ?? null) ? $state['players'] : [],
            'is_array'
        ));
        $title = trim((string) ($state['title'] ?? ''));
        $columns = [
            'status' => $status,
            'seq' => $seq,
            'mode' => (string) ($state['mode'] ?? 'x01'),
            'title' => mb_substr($title !== '' ? $title : (string) ($state['mode'] ?? 'Zápas'), 0, 160),
            'player_count' => max(1, count($players)),
            'has_bot' => (int) in_array(true, array_column($players, 'bot'), true),
            'is_solo' => (int) (count($players) < 2),
            'result' => 'none',
            'average' => null,
            'darts_thrown' => array_sum(array_map(static fn (array $player): int => (int) ($player['darts'] ?? 0), $players)),
            'play_seconds' => (int) ($state['played'] ?? 0),
            'finished_at' => $status === 'live' ? null : $now,
            'updated_at' => $now,
            'log' => self::json(['board' => $state]),
        ];
        if ($startedAt !== null) {
            $columns['started_at'] = $startedAt;
        }

        return $columns;
    }

    private static function newPublicId(): string
    {
        return bin2hex(random_bytes(12));
    }

    /**
     * Doplní hráčům webový účet: majitel je uživatel, který zápas nahrál,
     * kamarád nebo host se k účtu připojí podle shodného username.
     *
     * @param list<array<string, mixed>> $players
     * @return list<array<string, mixed>>
     */
    private function linkAccounts(int $userId, array $players): array
    {
        $usernames = array_values(array_unique(array_filter(array_column($players, 'username'))));
        $accounts = [];
        if ($usernames !== []) {
            $placeholders = implode(', ', array_fill(0, count($usernames), '?'));
            foreach ($this->db->fetchAll("SELECT id, username FROM users WHERE status = 'active' AND username IN ({$placeholders})", $usernames) as $row) {
                $accounts[mb_strtolower((string) $row['username'])] = (int) $row['id'];
            }
        }

        return array_map(static function (array $player) use ($userId, $accounts): array {
            $player['userId'] = match (true) {
                $player['kind'] === 'owner' => $userId,
                in_array($player['kind'], ['friend', 'guest'], true) && $player['username'] !== null => $accounts[mb_strtolower($player['username'])] ?? null,
                default => null,
            };

            return $player;
        }, $players);
    }
}
