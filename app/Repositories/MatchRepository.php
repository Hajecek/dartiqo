<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDOException;

final class MatchRepository
{
    public function __construct(private readonly Database $db)
    {
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
            'log' => json_encode($log, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
        ];

        return $this->db->transaction(function (Database $db) use ($userId, $clientId, $row): string {
            $existing = $db->fetch('SELECT id, public_id FROM matches WHERE user_id = :user AND client_id = :client FOR UPDATE', [
                'user' => $userId,
                'client' => $clientId,
            ]);
            if ($existing !== null) {
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
        $row['log'] = json_decode((string) $row['log'], true);
        unset($row['id'], $row['user_id']);

        return $row;
    }

    /**
     * Všechny zápasy účtu od nejnovějšího, s dekódovaným logem.
     *
     * @return list<array<string, mixed>>
     */
    public function allForUser(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT public_id, status, mode, title, is_solo, result, average, play_seconds, started_at, finished_at, log
             FROM matches WHERE user_id = :user ORDER BY started_at DESC, id DESC',
            ['user' => $userId]
        );
        foreach ($rows as &$row) {
            $row['log'] = json_decode((string) $row['log'], true) ?: [];
        }

        return $rows;
    }

    public function delete(int $userId, string $clientId): void
    {
        $this->db->query('DELETE FROM matches WHERE user_id = :user AND client_id = :client', [
            'user' => $userId,
            'client' => $clientId,
        ]);
    }

    /** Smazání z webu podle veřejného id. Cizí zápas nesmaže a vrací false. */
    public function deleteByPublicId(int $userId, string $publicId): bool
    {
        if (!preg_match('/^[0-9a-f]{24}$/', $publicId)) {
            return false;
        }

        return $this->db->query('DELETE FROM matches WHERE public_id = :public AND user_id = :user', [
            'public' => $publicId,
            'user' => $userId,
        ])->rowCount() > 0;
    }

    /** @return list<string> */
    public function clientIds(int $userId): array
    {
        return array_column($this->db->fetchAll('SELECT client_id FROM matches WHERE user_id = :user ORDER BY started_at', ['user' => $userId]), 'client_id');
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
