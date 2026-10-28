<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Zápasové XP ze stejných pravidel jako aplikace (MatchRewards + PlayerLevel).
 * Web je zdroj pro přihlášený účet, ať online zápas i druhý telefon ukážou stejný level.
 */
final class PlayerXp
{
    private const TIMEZONE = 'Europe/Prague';
    private const BASE = 500;
    private const GROWTH = 0.15;
    private const PLAYED = 5;
    private const COMPLETED = 15;
    private const WIN = 30;
    private const DRAW = 15;
    private const LOSS = -10;
    private const FULL_LEGS = 3;
    private const MIN_CHECKOUT_ATTEMPTS = 3;
    private const MIN_AVERAGE_DARTS = 3;
    private const ONE_EIGHTY = 10;
    private const ONE_EIGHTY_CAP = 30;
    private const HIGH_CHECKOUT = 15;
    private const HIGH_CHECKOUT_MIN = 100;
    private const HIGH_CHECKOUT_CAP = 30;
    private const REPEAT_FULL = 3;
    private const REPEAT_HALF = 5;
    private const REPEAT_WINDOW = 86400;
    private const BOT_COEFFICIENT = 0.5;
    private const BOT_DAILY_CAP = 10;
    private const SHORT_DARTS = 12;
    private const SECONDS_PER_DART = 1.2;
    private const OUTLIER_SAMPLE = 5;
    private const OUTLIER_DARTS = 9;
    private const OUTLIER_FLOOR = 100.0;
    private const OUTLIER_GAP = 35.0;
    private const REPEAT_WIN_FLAG = 5;
    private const GIVEAWAY_FLAG = 3;

    public function __construct(private readonly Database $db)
    {
    }

    /** @return array{total:int,level:int,intoLevel:int,needed:int,rank:string} */
    public function snapshot(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT client_id, kind, play_seconds, finished_at, log
             FROM matches
             WHERE user_id = :user AND status = \'finished\'
               AND JSON_EXTRACT(log, \'$.version\') IS NOT NULL
             ORDER BY finished_at ASC, client_id ASC',
            ['user' => $userId]
        );
        foreach ($rows as &$row) {
            $row['log'] = json_decode((string) $row['log'], true) ?: [];
        }
        unset($row);

        return self::progress(self::total($rows));
    }

    /** @param list<array<string, mixed>> $rows řádky matches s dekódovaným logem, od nejstaršího */
    public static function total(array $rows): int
    {
        $total = 0;
        $prior = [];
        foreach ($rows as $row) {
            if (!is_array($row['log'] ?? null)) {
                continue;
            }
            $award = self::award($row, $prior);
            $total += $award['amount'];
            $prior[] = $award['fact'];
        }

        return $total;
    }

    /** @return array{total:int,level:int,intoLevel:int,needed:int,rank:string} */
    public static function progress(int $xp): array
    {
        $safe = max(0, $xp);
        $level = 1;
        $spent = 0;
        while (true) {
            $need = self::xpToAdvance($level);
            if ($spent + $need > $safe || $level > 10000) {
                break;
            }
            $spent += $need;
            $level++;
        }
        $needed = self::xpToAdvance($level);
        $into = max(0, min($needed, $safe - $spent));

        return [
            'total' => $safe,
            'level' => $level,
            'intoLevel' => $into,
            'needed' => $needed,
            'rank' => match (true) {
                $level >= 10 => 'Expert',
                $level >= 5 => 'Pokročilý',
                default => 'Nováček',
            },
        ];
    }

    public static function xpToAdvance(int $level): int
    {
        $safe = max(1, $level);
        $raw = self::BASE * ((1 + self::GROWTH) ** ($safe - 1));

        return max(self::BASE, (int) round($raw));
    }

    /**
     * @param array<string, mixed> $row
     * @param list<array<string, mixed>> $prior
     * @return array{amount:int,fact:array<string, mixed>}
     */
    private static function award(array $row, array $prior): array
    {
        $log = $row['log'];
        $players = is_array($log['players'] ?? null) ? $log['players'] : [];
        $me = 0;
        foreach ($players as $index => $player) {
            if (is_array($player) && ($player['kind'] ?? '') === 'owner') {
                $me = $index;
                break;
            }
        }
        $mine = is_array($players[$me] ?? null) ? $players[$me] : [];
        $stats = is_array($mine['stats'] ?? null) ? $mine['stats'] : [];
        $darts = (int) ($stats['darts'] ?? 0);
        $stamp = self::stamp($row);
        $fact = self::fact($row, $log, $me, $stamp, $darts, $stats);

        if ($darts < 1 || self::ledgerBreaks($log, count($players))) {
            return ['amount' => 0, 'fact' => $fact];
        }

        $config = is_array($log['config'] ?? null) ? $log['config'] : [];
        $mode = (string) ($config['mode'] ?? '');
        $legs = self::completedLegs($log);
        $result = (string) ($mine['result'] ?? 'none');
        $raw = self::PLAYED + self::COMPLETED + match ($result) {
            'won' => self::WIN,
            'lost' => self::LOSS,
            'draw' => self::DRAW,
            default => 0,
        };
        if ($legs >= self::FULL_LEGS && $mode === 'x01') {
            $raw += self::performance($stats, $log, $me, $legs, $darts);
        }

        $venue = self::venue($row, $players, $me);
        $keys = self::keys($players, $me, $log);
        $repeat = self::repeatCount($keys, $prior, $stamp);
        $botToday = 0;
        if ($venue === 'bot') {
            $day = $fact['day'];
            foreach ($prior as $earlier) {
                if ($earlier['venue'] === 'bot' && $earlier['day'] === $day) {
                    $botToday++;
                }
            }
        }
        $botDarts = self::opponentDarts($players, $me, true);

        $zero = match ($venue) {
            'solo' => true,
            'ghost' => true,
            'local' => true,
            'bot' => $botDarts < 1 || $botToday >= self::BOT_DAILY_CAP,
            default => false,
        };
        if (!$zero && ($venue === 'online' || $venue === 'bot') && $repeat >= self::REPEAT_HALF) {
            $zero = true;
        }

        $coefficient = 0.0;
        if (!$zero) {
            $repeatFactor = $repeat >= self::REPEAT_HALF ? 0.0 : ($repeat >= self::REPEAT_FULL ? 0.5 : 1.0);
            $venueFactor = $venue === 'bot' ? self::BOT_COEFFICIENT : 1.0;
            $coefficient = $venueFactor * $repeatFactor;
        }
        if ($coefficient > 0 && self::withhold($fact, $prior, $stamp, $keys)) {
            $coefficient = 0.0;
        }

        return ['amount' => (int) round($raw * $coefficient), 'fact' => $fact];
    }

    /** @param array<string, mixed> $stats @param array<string, mixed> $log */
    private static function performance(array $stats, array $log, int $me, int $legs, int $darts): int
    {
        $amount = 0;
        $average = isset($stats['average']) ? (float) $stats['average'] : null;
        if ($average !== null && $darts >= self::MIN_AVERAGE_DARTS) {
            $amount += self::band($average, [
                [0, 30, 5], [30, 40, 10], [40, 50, 15], [50, 60, 20], [60, 70, 25],
                [70, 80, 30], [80, 90, 35], [90, 100, 40], [100, 110, 45], [110, null, 50],
            ]);
        }
        $attempts = (int) ($stats['checkoutAttempts'] ?? 0);
        $hits = (int) ($stats['checkouts'] ?? 0);
        if ($attempts >= self::MIN_CHECKOUT_ATTEMPTS && $hits > 0) {
            $rate = min(100, $hits / $attempts * 100);
            $amount += self::band($rate, [
                [0, 10, 5], [10, 20, 10], [20, 30, 15], [30, 40, 20], [40, 50, 25],
                [50, 60, 30], [60, 70, 35], [70, 80, 40], [80, 90, 45], [90, 100, 50],
            ], true);
        }
        $oneEighties = (int) ($stats['scores180'] ?? 0);
        $amount += min(self::ONE_EIGHTY_CAP, $oneEighties * self::ONE_EIGHTY);
        $high = 0;
        foreach (is_array($log['visits'] ?? null) ? $log['visits'] : [] as $visit) {
            if (!is_array($visit) || (int) ($visit['player'] ?? -1) !== $me) {
                continue;
            }
            if (!empty($visit['checkout']) && empty($visit['bust']) && (int) ($visit['score'] ?? 0) >= self::HIGH_CHECKOUT_MIN) {
                $high++;
            }
        }
        $amount += min(self::HIGH_CHECKOUT_CAP, $high * self::HIGH_CHECKOUT);

        return $amount;
    }

    /** @param list<array{0:float,1:?float,2:int}> $bands */
    private static function band(float $value, array $bands, bool $lastInclusive = false): int
    {
        $last = count($bands) - 1;
        foreach ($bands as $index => [$from, $to, $xp]) {
            if ($value < $from) {
                continue;
            }
            if ($to === null || $value < $to || ($lastInclusive && $index === $last && $value <= $to)) {
                return $xp;
            }
        }

        return 0;
    }

    /** @param array<string, mixed> $log */
    private static function completedLegs(array $log): int
    {
        $legs = [];
        foreach (is_array($log['legs'] ?? null) ? $log['legs'] : [] as $leg) {
            if (is_array($leg) && ($leg['winner'] ?? null) !== null) {
                $legs[(int) ($leg['leg'] ?? 0)] = true;
            }
        }
        if ($legs !== []) {
            return count($legs);
        }
        foreach (is_array($log['visits'] ?? null) ? $log['visits'] : [] as $visit) {
            if (is_array($visit) && !empty($visit['checkout'])) {
                $legs[(int) ($visit['leg'] ?? 0)] = true;
            }
        }

        return count($legs);
    }

    /**
     * @param array<string, mixed> $row
     * @param list<array<string, mixed>> $players
     */
    private static function venue(array $row, array $players, int $me): string
    {
        $humans = 0;
        $bots = 0;
        $ghosts = 0;
        foreach ($players as $index => $player) {
            if ($index === $me || !is_array($player)) {
                continue;
            }
            $kind = (string) ($player['kind'] ?? '');
            if ($kind === 'bot' || is_array($player['bot'] ?? null)) {
                $bots++;
            } elseif ($kind === 'ghost') {
                $ghosts++;
            } else {
                $humans++;
            }
        }
        $others = $humans + $bots + $ghosts;
        if ($others === 0) {
            return 'solo';
        }
        if ($humans > 0) {
            return ($row['kind'] ?? 'local') === 'online' ? 'online' : 'local';
        }
        if ($bots > 0) {
            return 'bot';
        }
        if ($ghosts === $others) {
            return 'ghost';
        }

        return 'local';
    }

    /** @param list<array<string, mixed>> $players @param array<string, mixed> $log @return list<string> */
    private static function keys(array $players, int $me, array $log): array
    {
        $ghost = is_array($log['ghost'] ?? null) ? (string) ($log['ghost']['kind'] ?? 'ghost') : 'ghost';
        $keys = [];
        foreach ($players as $index => $player) {
            if ($index === $me || !is_array($player)) {
                continue;
            }
            $kind = (string) ($player['kind'] ?? '');
            if ($kind === 'bot' || is_array($player['bot'] ?? null)) {
                $level = (int) ($player['bot']['level'] ?? 0);
                $keys[] = 'b-' . $level;
            } elseif ($kind === 'ghost') {
                $keys[] = 'g-' . ($ghost !== '' ? $ghost : 'ghost');
            } elseif ((int) ($player['userId'] ?? 0) > 0) {
                $keys[] = 'u-' . (int) $player['userId'];
            } else {
                $keys[] = 'p-' . strtolower((string) ($player['id'] ?? $index));
            }
        }

        return $keys;
    }

    /** @param list<string> $keys @param list<array<string, mixed>> $prior */
    private static function repeatCount(array $keys, array $prior, int $stamp): int
    {
        if ($keys === []) {
            return 0;
        }
        $best = 0;
        foreach ($keys as $key) {
            $count = 0;
            foreach ($prior as $earlier) {
                if ($stamp - (int) $earlier['at'] <= self::REPEAT_WINDOW && in_array($key, $earlier['keys'], true)) {
                    $count++;
                }
            }
            $best = max($best, $count);
        }

        return $best;
    }

    /** @param list<array<string, mixed>> $players */
    private static function opponentDarts(array $players, int $me, bool $botsOnly): int
    {
        $darts = 0;
        foreach ($players as $index => $player) {
            if ($index === $me || !is_array($player)) {
                continue;
            }
            $kind = (string) ($player['kind'] ?? '');
            $isBot = $kind === 'bot' || is_array($player['bot'] ?? null);
            if ($botsOnly && !$isBot) {
                continue;
            }
            $darts += (int) ($player['stats']['darts'] ?? 0);
        }

        return $darts;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $log
     * @param array<string, mixed> $stats
     * @return array<string, mixed>
     */
    private static function fact(array $row, array $log, int $me, int $stamp, int $darts, array $stats): array
    {
        $players = is_array($log['players'] ?? null) ? $log['players'] : [];
        $mine = is_array($players[$me] ?? null) ? $players[$me] : [];
        $won = ($mine['result'] ?? '') === 'won';
        $theirs = self::opponentDarts($players, $me, false);
        $zone = new DateTimeZone(self::TIMEZONE);
        $day = (new DateTimeImmutable('@' . $stamp))->setTimezone($zone)->format('Y-m-d');
        $allDarts = 0;
        foreach ($players as $player) {
            if (is_array($player)) {
                $allDarts += (int) ($player['stats']['darts'] ?? 0);
            }
        }

        return [
            'at' => $stamp,
            'day' => $day,
            'venue' => self::venue($row, $players, $me),
            'keys' => self::keys($players, $me, $log),
            'won' => $won,
            'mode' => (string) ($log['config']['mode'] ?? ''),
            'average' => isset($stats['average']) ? (float) $stats['average'] : null,
            'ownerDarts' => $darts,
            'allDarts' => $allDarts,
            'seconds' => (int) ($row['play_seconds'] ?? $log['playSeconds'] ?? 0),
            'giveaway' => $won && $darts > 0 && $theirs === 0,
        ];
    }

    /** @param array<string, mixed> $fact @param list<array<string, mixed>> $prior @param list<string> $keys */
    private static function withhold(array $fact, array $prior, int $stamp, array $keys): bool
    {
        $flags = [];
        if ($fact['allDarts'] >= self::SHORT_DARTS && $fact['seconds'] > 0 && $fact['seconds'] < $fact['allDarts'] * self::SECONDS_PER_DART) {
            $flags[] = 'short-time';
        }
        if (self::outlier($fact, $prior)) {
            $flags[] = 'outlier';
        }
        if ($fact['won'] && self::repeatWins($prior, $stamp, $keys) + 1 >= self::REPEAT_WIN_FLAG) {
            $flags[] = 'repeat-wins';
        }
        $giveaways = ($fact['giveaway'] ? 1 : 0);
        foreach ($prior as $earlier) {
            if ($stamp - (int) $earlier['at'] <= self::REPEAT_WINDOW && !empty($earlier['giveaway'])) {
                $giveaways++;
            }
        }
        if ($giveaways >= self::GIVEAWAY_FLAG) {
            return true;
        }
        $soft = array_intersect($flags, ['short-time', 'outlier', 'repeat-wins']);

        return count($soft) >= 2;
    }

    /** @param array<string, mixed> $fact @param list<array<string, mixed>> $prior */
    private static function outlier(array $fact, array $prior): bool
    {
        if ($fact['mode'] !== 'x01' || $fact['average'] === null || $fact['average'] < self::OUTLIER_FLOOR) {
            return false;
        }
        $samples = [];
        foreach ($prior as $earlier) {
            if ($earlier['mode'] === 'x01' && $earlier['ownerDarts'] >= self::OUTLIER_DARTS && $earlier['average'] !== null) {
                $samples[] = (float) $earlier['average'];
            }
        }
        if (count($samples) < self::OUTLIER_SAMPLE) {
            return false;
        }
        sort($samples);
        $mid = intdiv(count($samples), 2);
        $median = count($samples) % 2 === 0 ? ($samples[$mid - 1] + $samples[$mid]) / 2 : $samples[$mid];

        return $fact['average'] >= $median + self::OUTLIER_GAP;
    }

    /** @param list<array<string, mixed>> $prior @param list<string> $keys */
    private static function repeatWins(array $prior, int $stamp, array $keys): int
    {
        $wins = 0;
        foreach ($prior as $earlier) {
            if (!$earlier['won'] || $stamp - (int) $earlier['at'] > self::REPEAT_WINDOW) {
                continue;
            }
            if (array_intersect($keys, $earlier['keys']) !== []) {
                $wins++;
            }
        }

        return $wins;
    }

    /** @param array<string, mixed> $log */
    private static function ledgerBreaks(array $log, int $players): bool
    {
        $winner = $log['winner'] ?? null;
        if ($winner !== null && (!is_int($winner) || $winner < 0 || $winner >= $players)) {
            return true;
        }
        foreach (is_array($log['visits'] ?? null) ? $log['visits'] : [] as $visit) {
            if (!is_array($visit)) {
                return true;
            }
            $player = $visit['player'] ?? -1;
            if (!is_int($player) || $player < 0 || $player >= $players) {
                return true;
            }
            $darts = $visit['darts'] ?? [];
            if (!is_array($darts) || count($darts) < 1 || count($darts) > 3) {
                return true;
            }
            $score = (int) ($visit['score'] ?? 0);
            if ($score < 0 || $score > 180) {
                return true;
            }
            if (!empty($visit['bust']) && $score !== 0) {
                return true;
            }
            if (!empty($visit['checkout']) && array_key_exists('after', $visit) && (int) $visit['after'] !== 0) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $row */
    private static function stamp(array $row): int
    {
        $raw = $row['finished_at'] ?? null;
        if (is_string($raw) && $raw !== '') {
            $parsed = strtotime($raw . ' UTC');
            if ($parsed !== false) {
                return $parsed;
            }
        }
        $fromLog = $row['log']['finishedAt'] ?? null;
        if (is_string($fromLog)) {
            $parsed = strtotime($fromLog);
            if ($parsed !== false) {
                return $parsed;
            }
        }

        return time();
    }
}
