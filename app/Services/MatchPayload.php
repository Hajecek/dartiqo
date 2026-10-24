<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Odehraný zápas z mobilní aplikace. Projde jen známá pole se správným typem
 * a rozsahem; statistiky se pak počítají z hodů v MatchStats, ne z čísel aplikace.
 */
final class MatchPayload
{
    private const STATUSES = ['finished', 'abandoned'];
    private const MODES = ['x01', 'cricket', 'aroundClock', 'countUp'];
    private const OUT_RULES = ['double', 'straight', 'master'];
    private const KINDS = ['owner', 'friend', 'guest', 'bot', 'ghost'];
    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
    private const MAX_VISITS = 3000;
    private const STANDARD_CRICKET = [20, 19, 18, 17, 16, 15, 25];

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function parse(array $input): array
    {
        $status = (string) ($input['status'] ?? '');
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Neznámý stav zápasu.');
        }
        $config = $input['config'] ?? null;
        if (!is_array($config)) {
            throw new InvalidArgumentException('Chybí nastavení zápasu.');
        }
        $mode = (string) ($config['mode'] ?? '');
        if (!in_array($mode, self::MODES, true)) {
            throw new InvalidArgumentException('Neznámý herní mód.');
        }

        $players = $input['players'] ?? null;
        if (!is_array($players) || !array_is_list($players) || count($players) < 1 || count($players) > 4) {
            throw new InvalidArgumentException('Zápas musí mít 1 až 4 hráče.');
        }
        $players = array_map(self::player(...), $players);
        $count = count($players);

        $visits = $input['visits'] ?? [];
        if (!is_array($visits) || !array_is_list($visits) || count($visits) > self::MAX_VISITS) {
            throw new InvalidArgumentException('Neplatný seznam hodů.');
        }
        $visits = array_map(static fn (mixed $visit): array => self::visit($visit, $count), $visits);

        $winner = $input['winner'] ?? null;
        $winner = $status === 'finished' && is_int($winner) && $winner >= 0 && $winner < $count ? $winner : null;

        $bullOffLegs = [];
        foreach ((array) ($input['bullOffLegs'] ?? []) as $leg => $player) {
            if (is_numeric($leg) && (int) $leg >= 1 && is_int($player) && $player >= 0 && $player < $count) {
                $bullOffLegs[(int) $leg] = $player;
            }
        }

        $started = self::time($input['startedAt'] ?? null) ?? time();
        $finished = self::time($input['finishedAt'] ?? null);
        if ($finished !== null && $finished < $started) {
            $finished = $started;
        }

        $ghost = is_array($input['ghost'] ?? null) ? $input['ghost'] : null;
        $raw = $input['match'] ?? null;

        return [
            'status' => $status,
            'title' => self::text($input['title'] ?? '', 160) ?: $mode,
            'startedAt' => $started,
            'finishedAt' => $finished,
            'playSeconds' => self::int($input['playSeconds'] ?? 0, 0, 86_400),
            'config' => self::config($mode, $config),
            'ghost' => $ghost === null ? null : [
                'kind' => self::text($ghost['kind'] ?? '', 30) ?: null,
                'label' => self::text($ghost['label'] ?? '', 80) ?: null,
                'average' => self::decimal($ghost['average'] ?? null, 0, 200),
            ],
            'openingStarter' => self::int($input['openingStarter'] ?? 0, 0, $count - 1),
            'winner' => $winner,
            'bullOffLegs' => $bullOffLegs,
            'players' => $players,
            'visits' => $visits,
            'app' => [
                'version' => self::text($input['appVersion'] ?? '', 20) ?: null,
                'device' => self::text($input['device'] ?? '', 100) ?: null,
            ],
            'raw' => is_array($raw) ? $raw : null,
        ];
    }

    /**
     * Pravidla jen s poli, která pro daný mód něco znamenají.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private static function config(string $mode, array $config): array
    {
        $rules = [
            'mode' => $mode,
            'format' => self::choice($config['format'] ?? 'firstTo', ['firstTo', 'bestOf'], 'firstTo'),
            'legsToWin' => self::int($config['legsToWin'] ?? 1, 1, 15),
            'setsToWin' => self::int($config['setsToWin'] ?? 1, 1, 11),
            'entry' => self::choice($config['entry'] ?? 'darts', ['darts', 'total'], 'darts'),
        ];

        return $rules + match ($mode) {
            'x01' => [
                'startingScore' => self::int($config['startingScore'] ?? 501, 2, 1701),
                'outRule' => self::choice($config['outRule'] ?? 'double', self::OUT_RULES, 'double'),
                'doubleIn' => ($config['doubleIn'] ?? false) === true,
                'dartLimit' => is_int($config['dartLimit'] ?? null) ? self::int($config['dartLimit'], 3, 300) : null,
            ],
            'cricket' => [
                'cricketNumbers' => self::cricketNumbers($config['cricketNumbers'] ?? null),
                'cricketNoScore' => ($config['cricketNoScore'] ?? false) === true,
            ],
            'aroundClock' => [
                'clockStyle' => self::choice($config['clockStyle'] ?? 'any', ['any', 'doubles', 'triples'], 'any'),
            ],
            'countUp' => [
                'countUpRounds' => self::int($config['countUpRounds'] ?? 10, 1, 30),
            ],
        };
    }

    /** @return array<string, mixed> */
    private static function player(mixed $player): array
    {
        if (!is_array($player)) {
            throw new InvalidArgumentException('Neplatný hráč.');
        }
        $id = strtolower((string) ($player['id'] ?? ''));
        if (!preg_match(self::UUID, $id)) {
            throw new InvalidArgumentException('Hráč nemá platné id.');
        }
        $kind = self::choice($player['kind'] ?? 'guest', self::KINDS, 'guest');
        $bot = is_array($player['bot'] ?? null) ? $player['bot'] : null;
        if ($bot === null && $kind === 'bot') {
            $kind = 'guest';
        }
        $handicap = is_array($player['handicap'] ?? null) ? $player['handicap'] : null;
        $final = is_array($player['final'] ?? null) ? $player['final'] : [];
        $username = self::text($player['username'] ?? '', 30);

        return [
            'id' => $id,
            'kind' => $kind,
            'name' => self::text($player['name'] ?? '', 40) ?: 'Hráč',
            'username' => $username !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $username) ? $username : null,
            'bot' => $bot === null ? null : [
                'level' => is_int($bot['level'] ?? null) ? self::int($bot['level'], 1, 99) : null,
                'style' => self::text($bot['style'] ?? '', 30) ?: null,
                'adaptive' => ($bot['adaptive'] ?? false) === true,
                'average' => self::decimal($bot['average'] ?? null, 0, 200),
                'checkout' => self::decimal($bot['checkout'] ?? null, 0, 1),
                'consistency' => self::decimal($bot['consistency'] ?? null, 0, 1),
                'pressure' => self::decimal($bot['pressure'] ?? null, 0, 1),
                'intelligence' => self::decimal($bot['intelligence'] ?? null, 0, 1),
            ],
            'handicap' => $handicap === null ? null : [
                'startingScore' => is_int($handicap['startingScore'] ?? null) ? self::int($handicap['startingScore'], 2, 1701) : null,
                'outRule' => isset($handicap['outRule']) ? self::choice($handicap['outRule'], self::OUT_RULES, 'double') : null,
                'doubleIn' => is_bool($handicap['doubleIn'] ?? null) ? $handicap['doubleIn'] : null,
            ],
            'final' => [
                'remaining' => is_int($final['remaining'] ?? null) ? self::int($final['remaining'], 0, 1701) : null,
                'points' => is_int($final['points'] ?? null) ? self::int($final['points'], -100_000, 100_000) : null,
                'clockTarget' => is_int($final['clockTarget'] ?? null) ? self::int($final['clockTarget'], 1, 22) : null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function visit(mixed $visit, int $players): array
    {
        if (!is_array($visit)) {
            throw new InvalidArgumentException('Neplatný hod.');
        }
        $player = $visit['player'] ?? null;
        if (!is_int($player) || $player < 0 || $player >= $players) {
            throw new InvalidArgumentException('Hod patří neznámému hráči.');
        }
        $darts = $visit['darts'] ?? null;
        if (!is_array($darts) || !array_is_list($darts) || count($darts) < 1 || count($darts) > 3) {
            throw new InvalidArgumentException('Návštěva musí mít 1 až 3 šipky.');
        }

        return [
            'player' => $player,
            'leg' => self::int($visit['leg'] ?? 1, 1, 999),
            'darts' => array_map(self::dart(...), $darts),
            'score' => self::int($visit['score'] ?? 0, 0, 180),
            'remaining' => is_int($visit['remaining'] ?? null) ? self::int($visit['remaining'], 0, 1701) : null,
            'bust' => ($visit['bust'] ?? false) === true,
            'checkout' => ($visit['checkout'] ?? false) === true,
            'total' => ($visit['total'] ?? false) === true,
        ];
    }

    /** @return array{segment:int, multiplier:int, label:string, score:int, x:?float, y:?float} */
    private static function dart(mixed $dart): array
    {
        $segment = is_array($dart) ? ($dart['s'] ?? null) : null;
        $multiplier = is_array($dart) ? ($dart['m'] ?? null) : null;
        $valid = is_int($segment) && is_int($multiplier) && match (true) {
            $segment === 0 => $multiplier === 1,
            $segment === 25 => $multiplier === 1 || $multiplier === 2,
            default => $segment >= 1 && $segment <= 20 && $multiplier >= 1 && $multiplier <= 3,
        };
        if (!$valid) {
            throw new InvalidArgumentException('Neplatná šipka.');
        }
        $coordinate = static fn (mixed $value): ?float => is_int($value) || is_float($value) ? round(max(-2.0, min(2.0, (float) $value)), 4) : null;

        return [
            'segment' => $segment,
            'multiplier' => $multiplier,
            'label' => match (true) {
                $segment === 0 => 'MISS',
                $segment === 25 => $multiplier === 2 ? 'BULL' : '25',
                default => ['', 'S', 'D', 'T'][$multiplier] . $segment,
            },
            'score' => $segment * $multiplier,
            'x' => $coordinate($dart['x'] ?? null),
            'y' => $coordinate($dart['y'] ?? null),
        ];
    }

    /** @return list<int> */
    private static function cricketNumbers(mixed $raw): array
    {
        $numbers = is_array($raw) ? array_values(array_unique(array_filter($raw, static fn (mixed $n): bool => is_int($n) && (($n >= 1 && $n <= 20) || $n === 25)))) : [];

        return $numbers === [] ? self::STANDARD_CRICKET : $numbers;
    }

    private static function time(mixed $value): ?int
    {
        $time = is_string($value) ? strtotime($value) : false;

        return $time === false || $time > time() + 300 ? null : $time;
    }

    private static function text(mixed $value, int $max): string
    {
        $text = is_scalar($value) ? trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '') : '';

        return mb_substr($text, 0, $max);
    }

    private static function int(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, is_numeric($value) ? (int) $value : $min));
    }

    private static function decimal(mixed $value, float $min, float $max): ?float
    {
        if (!is_int($value) && !is_float($value)) {
            return null;
        }

        return round(max($min, min($max, (float) $value)), 3);
    }

    /** @param list<string> $allowed */
    private static function choice(mixed $value, array $allowed, string $fallback): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $fallback;
    }
}
