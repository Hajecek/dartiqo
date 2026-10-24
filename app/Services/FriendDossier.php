<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Statistiky kamaráda a vzájemné zápasy. Vidí je jen přijatý kamarád.
 * Zápas se počítá, když v logu je účet druhého hráče. Když ho nahrály oba telefony,
 * zůstane jedna kopie, přednost má zápas přihlášeného hráče.
 */
final class FriendDossier
{
    private const TIMEZONE = 'Europe/Prague';

    public function __construct(private readonly Database $db)
    {
    }

    /**
     * @param array{friends: list<array<string, mixed>>, incoming: list<array<string, mixed>>, outgoing: list<array<string, mixed>>} $book
     * @return array{friends: list<array<string, mixed>>, incoming: list<array<string, mixed>>, outgoing: list<array<string, mixed>>}
     */
    public function withSummaries(int $viewerId, array $book): array
    {
        $friends = [];
        foreach ($book['friends'] as $link) {
            $id = (int) ($link['user']['id'] ?? 0);
            if ($id > 0) {
                $friends[$id] = (string) ($link['user']['username'] ?? '');
            }
        }
        $summaries = $this->collect($viewerId, $friends, false);
        foreach ($book['friends'] as $index => $link) {
            $id = (int) ($link['user']['id'] ?? 0);
            $book['friends'][$index]['summary'] = $summaries[$id]['summary'] ?? self::emptySummary();
        }

        return $book;
    }

    /**
     * @return array{career: array<string, mixed>, mutual: array<string, int>, myAverage: ?float, theirAverage: ?float, standing: string, games: list<array<string, mixed>>}
     */
    public function detail(int $viewerId, int $friendId): array
    {
        $names = $this->usernames([$friendId]);
        $row = $this->collect($viewerId, [$friendId => $names[$friendId] ?? ''], true)[$friendId];
        unset($row['summary']);

        return $row;
    }

    /**
     * @param array<int, string> $friends id => username
     * @return array<int, array<string, mixed>>
     */
    private function collect(int $viewerId, array $friends, bool $withGames): array
    {
        $hidden = array_fill_keys($this->hiddenIds(array_keys($friends)), true);
        $visible = array_diff_key($friends, $hidden);
        $careers = $this->careers(array_keys($visible));
        $series = $this->series($viewerId, $visible);
        $out = [];
        foreach ($friends as $id => $username) {
            if (isset($hidden[$id])) {
                $out[$id] = self::hiddenRecord();
                continue;
            }
            $career = $careers[$id] ?? self::emptyCareer();
            $mutual = $series[$id] ?? null;
            $summary = [
                'private' => false,
                'career' => $career,
                'mutual' => [
                    'wins' => $mutual['wins'] ?? 0,
                    'losses' => $mutual['losses'] ?? 0,
                    'draws' => $mutual['draws'] ?? 0,
                ],
            ];
            $out[$id] = [
                'private' => false,
                'summary' => $summary,
                'career' => $career,
                'mutual' => $summary['mutual'],
                'myAverage' => $mutual['myAverage'] ?? null,
                'theirAverage' => $mutual['theirAverage'] ?? null,
                'standing' => self::standing($summary['mutual']['wins'], $summary['mutual']['losses'], $summary['mutual']['draws']),
                'games' => $withGames ? array_slice($mutual['games'] ?? [], 0, 40) : [],
            ];
            unset($username);
        }

        return $out;
    }

    /**
     * @param list<int> $ids
     * @return array<int, array{played: int, wins: int, losses: int, draws: int, average: ?float}>
     */
    private function careers(array $ids): array
    {
        $ids = array_values(array_filter(array_map(intval(...), $ids), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $rows = $this->db->fetchAll(
            "SELECT user_id,
                    SUM(CASE WHEN is_solo = 0 AND result IN ('won', 'lost', 'draw') THEN 1 ELSE 0 END) AS played,
                    SUM(CASE WHEN is_solo = 0 AND result = 'won' THEN 1 ELSE 0 END) AS wins,
                    SUM(CASE WHEN is_solo = 0 AND result = 'lost' THEN 1 ELSE 0 END) AS losses,
                    AVG(CASE WHEN average IS NOT NULL THEN average END) AS average
             FROM matches
             WHERE status IN ('finished', 'deleted') AND user_id IN ({$placeholders})
             GROUP BY user_id",
            $ids
        );
        $out = [];
        foreach ($rows as $row) {
            $played = (int) $row['played'];
            $wins = (int) $row['wins'];
            $losses = (int) $row['losses'];
            $out[(int) $row['user_id']] = [
                'played' => $played,
                'wins' => $wins,
                'losses' => $losses,
                'draws' => max(0, $played - $wins - $losses),
                'average' => $row['average'] === null ? null : round((float) $row['average'], 1),
            ];
        }

        return $out;
    }

    /**
     * @param array<int, string> $friends
     * @return array<int, array{wins: int, losses: int, draws: int, myAverage: ?float, theirAverage: ?float, games: list<array<string, mixed>>}>
     */
    private function series(int $viewerId, array $friends): array
    {
        if ($friends === []) {
            return [];
        }
        $viewerName = $this->usernames([$viewerId])[$viewerId] ?? '';
        $rows = array_merge(
            $this->side($viewerId, array_keys($friends), false),
            $this->side($viewerId, array_keys($friends), true)
        );

        $kept = [];
        foreach ($rows as $row) {
            $players = self::players($row['players']);
            $me = self::playerFor($players, $viewerId, $viewerName);
            if ($me === null) {
                continue;
            }
            $ownerId = (int) $row['user_id'];
            foreach ($friends as $friendId => $friendName) {
                if ($ownerId !== $viewerId && $ownerId !== $friendId) {
                    continue;
                }
                $them = self::playerFor($players, $friendId, $friendName);
                if ($them === null || self::samePlayer($me, $them)) {
                    continue;
                }
                $game = $this->game($row, $me, $them, $ownerId === $viewerId);
                if ($game === null) {
                    continue;
                }
                $key = $friendId . '|' . $game['dedupe'];
                $current = $kept[$key] ?? null;
                if ($current === null || (!$current['mine'] && $game['mine'])) {
                    $kept[$key] = $game + ['friendId' => $friendId];
                }
            }
        }

        $bucket = [];
        foreach ($kept as $game) {
            $friendId = $game['friendId'];
            unset($game['friendId'], $game['dedupe'], $game['sort']);
            $bucket[$friendId][] = $game;
        }

        $out = [];
        foreach ($bucket as $friendId => $games) {
            usort($games, static fn (array $a, array $b): int => $b['at'] <=> $a['at']);
            $wins = 0;
            $losses = 0;
            $draws = 0;
            $myPoints = 0;
            $myDarts = 0;
            $theirPoints = 0;
            $theirDarts = 0;
            foreach ($games as $index => $game) {
                $wins += $game['result'] === 'win' ? 1 : 0;
                $losses += $game['result'] === 'loss' ? 1 : 0;
                $draws += $game['result'] === 'draw' ? 1 : 0;
                if ($game['scored']) {
                    $myPoints += $game['myPoints'];
                    $myDarts += $game['myDarts'];
                    $theirPoints += $game['theirPoints'];
                    $theirDarts += $game['theirDarts'];
                }
                unset(
                    $games[$index]['myPoints'],
                    $games[$index]['myDarts'],
                    $games[$index]['theirPoints'],
                    $games[$index]['theirDarts'],
                    $games[$index]['scored'],
                    $games[$index]['at']
                );
            }
            $out[$friendId] = [
                'wins' => $wins,
                'losses' => $losses,
                'draws' => $draws,
                'myAverage' => $myDarts > 0 ? round($myPoints / $myDarts * 3, 1) : null,
                'theirAverage' => $theirDarts > 0 ? round($theirPoints / $theirDarts * 3, 1) : null,
                'games' => array_values($games),
            ];
        }

        return $out;
    }

    /**
     * @param list<int> $friendIds
     * @return list<array<string, mixed>>
     */
    private function side(int $viewerId, array $friendIds, bool $theirs): array
    {
        $friendIds = array_values(array_filter($friendIds, static fn (int $id): bool => $id > 0));
        if ($friendIds === []) {
            return [];
        }
        if ($theirs) {
            $placeholders = implode(', ', array_fill(0, count($friendIds), '?'));
            $sql = "SELECT user_id, public_id, mode, title, started_at,
                           JSON_EXTRACT(log, '$.players') AS players,
                           JSON_EXTRACT(log, '$.config.setsToWin') AS sets_to_win,
                           JSON_EXTRACT(log, '$.config.startingScore') AS starting_score
                    FROM matches
                    WHERE status = 'finished' AND is_solo = 0 AND user_id IN ({$placeholders})
                      AND JSON_CONTAINS(log, ?, '$.players')
                    ORDER BY started_at DESC
                    LIMIT 200";

            return $this->db->fetchAll($sql, array_merge($friendIds, [json_encode(['userId' => $viewerId], JSON_THROW_ON_ERROR)]));
        }

        $needles = [];
        $params = [$viewerId];
        foreach ($friendIds as $id) {
            $needles[] = 'JSON_CONTAINS(log, ?, \'$.players\')';
            $params[] = json_encode(['userId' => $id], JSON_THROW_ON_ERROR);
        }
        $sql = "SELECT user_id, public_id, mode, title, started_at,
                       JSON_EXTRACT(log, '$.players') AS players,
                       JSON_EXTRACT(log, '$.config.setsToWin') AS sets_to_win,
                       JSON_EXTRACT(log, '$.config.startingScore') AS starting_score
                FROM matches
                WHERE status = 'finished' AND is_solo = 0 AND user_id = ?
                  AND (" . implode(' OR ', $needles) . ')
                ORDER BY started_at DESC
                LIMIT 200';

        return $this->db->fetchAll($sql, $params);
    }

    /** @param array<string, mixed> $row @param array<string, mixed> $me @param array<string, mixed> $them @return array<string, mixed>|null */
    private function game(array $row, array $me, array $them, bool $mine): ?array
    {
        $result = match ((string) ($me['result'] ?? 'none')) {
            'won' => 'win',
            'lost' => 'loss',
            'draw' => 'draw',
            default => null,
        };
        if ($result === null) {
            return null;
        }
        $sets = (int) $row['sets_to_win'] > 1;
        $myScore = $sets ? (int) ($me['setsWon'] ?? 0) : (int) ($me['legsWon'] ?? 0);
        $theirScore = $sets ? (int) ($them['setsWon'] ?? 0) : (int) ($them['legsWon'] ?? 0);
        $started = new DateTimeImmutable((string) $row['started_at'], new DateTimeZone('UTC'));
        $local = $started->setTimezone(new DateTimeZone(self::TIMEZONE));
        $myStats = is_array($me['stats'] ?? null) ? $me['stats'] : [];
        $theirStats = is_array($them['stats'] ?? null) ? $them['stats'] : [];
        $mode = (string) $row['mode'];

        return [
            'id' => (string) $row['public_id'],
            'when' => $local->format('j. n. Y') . ' · ' . $local->format('G:i'),
            'mode' => self::modeLabel($mode, (int) $row['starting_score']),
            'title' => (string) $row['title'],
            'score' => $myScore . ' : ' . $theirScore,
            'result' => $result,
            'label' => match ($result) {
                'win' => 'Výhra',
                'loss' => 'Prohra',
                default => 'Remíza',
            },
            'mine' => $mine,
            'myAverage' => self::statAverage($myStats, $mode),
            'theirAverage' => self::statAverage($theirStats, $mode),
            'scored' => in_array($mode, ['x01', 'countUp'], true),
            'myPoints' => (int) ($myStats['points'] ?? 0),
            'myDarts' => (int) ($myStats['darts'] ?? 0),
            'theirPoints' => (int) ($theirStats['points'] ?? 0),
            'theirDarts' => (int) ($theirStats['darts'] ?? 0),
            'at' => $started->getTimestamp(),
            'dedupe' => $mode . '|' . $local->format('Y-m-d H:i') . '|' . $myScore . '|' . $theirScore,
        ];
    }

    /** @param array<string, mixed> $stats */
    private static function statAverage(array $stats, string $mode): ?float
    {
        $key = $mode === 'cricket' ? 'mpr' : 'average';
        $value = $stats[$key] ?? null;

        return is_numeric($value) ? round((float) $value, 1) : null;
    }

    private static function modeLabel(string $mode, int $startingScore): string
    {
        return match ($mode) {
            'x01' => $startingScore > 0 ? (string) $startingScore : 'X01',
            'cricket' => 'Cricket',
            'aroundClock' => 'Kolem hodin',
            default => 'Count Up',
        };
    }

    /** @return list<array<string, mixed>> */
    private static function players(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? array_values(array_filter($raw, is_array(...))) : [];
    }

    /** @param list<array<string, mixed>> $players @return array<string, mixed>|null */
    private static function playerFor(array $players, int $userId, string $username): ?array
    {
        $username = mb_strtolower($username);
        $byName = null;
        foreach ($players as $player) {
            if ((int) ($player['userId'] ?? 0) === $userId) {
                return $player;
            }
            $handle = mb_strtolower((string) ($player['username'] ?? ''));
            if ($username !== '' && $handle === $username) {
                $byName = $player;
            }
        }

        return $byName;
    }

    /** @param array<string, mixed> $left @param array<string, mixed> $right */
    private static function samePlayer(array $left, array $right): bool
    {
        $leftId = (int) ($left['userId'] ?? 0);
        if ($leftId > 0 && $leftId === (int) ($right['userId'] ?? 0)) {
            return true;
        }

        return array_key_exists('position', $left)
            && array_key_exists('position', $right)
            && $left['position'] === $right['position'];
    }

    /** @param list<int> $ids @return array<int, string> */
    private function usernames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map(intval(...), $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $out = [];
        foreach ($this->db->fetchAll("SELECT id, username FROM users WHERE id IN ({$placeholders})", $ids) as $row) {
            $out[(int) $row['id']] = (string) $row['username'];
        }

        return $out;
    }

    public static function standing(int $wins, int $losses, int $draws): string
    {
        if ($wins + $losses + $draws === 0) {
            return 'Ještě jste spolu nehráli.';
        }
        if ($wins > $losses) {
            return 'Vedeš.';
        }
        if ($wins < $losses) {
            return 'Prohráváš.';
        }

        return 'Je to vyrovnané.';
    }

    /**
     * Soukromý účet: kamarád nedostane kariéru, průměry ani vzájemné zápasy.
     *
     * @return array<string, mixed>
     */
    private static function hiddenRecord(): array
    {
        $summary = self::emptySummary();
        $summary['private'] = true;

        return [
            'private' => true,
            'summary' => $summary,
            'career' => $summary['career'],
            'mutual' => $summary['mutual'],
            'myAverage' => null,
            'theirAverage' => null,
            'standing' => 'Soukromý účet. Statistiky nejsou vidět.',
            'games' => [],
        ];
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function hiddenIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map(intval(...), $ids), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $rows = $this->db->fetchAll(
            "SELECT id FROM users WHERE visibility = 'private' AND id IN ({$placeholders})",
            $ids
        );

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    /** @return array{played: int, wins: int, losses: int, draws: int, average: null} */
    private static function emptyCareer(): array
    {
        return ['played' => 0, 'wins' => 0, 'losses' => 0, 'draws' => 0, 'average' => null];
    }

    /** @return array{career: array{played: int, wins: int, losses: int, draws: int, average: null}, mutual: array{wins: int, losses: int, draws: int}} */
    private static function emptySummary(): array
    {
        return [
            'private' => false,
            'career' => self::emptyCareer(),
            'mutual' => ['wins' => 0, 'losses' => 0, 'draws' => 0],
        ];
    }
}
