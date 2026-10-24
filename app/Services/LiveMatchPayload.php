<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Stav zápasu z mobilní aplikace. Projde jen známé pole se správným typem,
 * takže do databáze ani do prohlížeče se nedostane nic navíc.
 */
final class LiveMatchPayload
{
    public const STATUSES = ['live', 'finished', 'abandoned'];
    private const MODES = ['x01', 'cricket', 'aroundClock', 'countUp'];
    private const DART = '/^(MISS|BULL|25|[SDT]([1-9]|1\d|20))$/';

    /**
     * @param array<string, mixed> $input
     * @return array{status:string,seq:int,startedAt:string,state:array<string,mixed>}
     */
    public static function parse(array $input): array
    {
        $status = (string) ($input['status'] ?? 'live');
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Neznámý stav zápasu.');
        }
        $seq = $input['seq'] ?? null;
        if (!is_int($seq) || $seq < 0 || $seq > 4_000_000_000) {
            throw new InvalidArgumentException('Chybí pořadí aktualizace.');
        }
        $mode = (string) ($input['mode'] ?? '');
        if (!in_array($mode, self::MODES, true)) {
            throw new InvalidArgumentException('Neznámý herní mód.');
        }
        $players = $input['players'] ?? null;
        if (!is_array($players) || !array_is_list($players) || count($players) < 1 || count($players) > 4) {
            throw new InvalidArgumentException('Zápas musí mít 1 až 4 hráče.');
        }
        $players = array_map(self::player(...), $players);
        $count = count($players);

        $started = strtotime((string) ($input['startedAt'] ?? ''));
        if ($started === false || $started > time() + 300) {
            $started = time();
        }

        return [
            'status' => $status,
            'seq' => $seq,
            'startedAt' => gmdate('Y-m-d H:i:s', $started),
            'state' => [
                'mode' => $mode,
                'title' => self::text($input['title'] ?? '', 120),
                'leg' => self::int($input['leg'] ?? 1, 1, 999),
                'set' => self::int($input['set'] ?? 1, 1, 99),
                'legsToWin' => self::int($input['legsToWin'] ?? 1, 1, 15),
                'setsToWin' => self::int($input['setsToWin'] ?? 1, 1, 11),
                'active' => self::int($input['active'] ?? 0, 0, $count - 1),
                'winner' => self::index($input['winner'] ?? null, $count),
                'legWinner' => self::index($input['legWinner'] ?? null, $count),
                'bullOff' => (bool) ($input['bullOff'] ?? false),
                'legSlots' => self::int($input['legSlots'] ?? 0, 0, 31),
                'setSlots' => self::int($input['setSlots'] ?? 0, 0, 21),
                'legAverage' => self::average($input['legAverage'] ?? null),
                'paused' => $status === 'live' && (bool) ($input['paused'] ?? false),
                'played' => self::int($input['played'] ?? 0, 0, 604800),
                'running' => $status === 'live' && empty($input['paused']) && (bool) ($input['running'] ?? false),
                'numbers' => self::numbers($input['numbers'] ?? []),
                'players' => $players,
                'currentDarts' => self::darts($input['currentDarts'] ?? [], 3),
                'visits' => self::visits($input['visits'] ?? [], $count),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function player(mixed $raw): array
    {
        if (!is_array($raw)) {
            throw new InvalidArgumentException('Neplatný hráč.');
        }
        $marks = [];
        if (is_array($raw['marks'] ?? null)) {
            foreach ($raw['marks'] as $number => $hits) {
                if (self::isBoardNumber($number) && is_int($hits)) {
                    $marks[(string) $number] = max(0, min(3, $hits));
                }
            }
        }

        $player = [
            'name' => self::text($raw['name'] ?? '', 40) ?: 'Hráč',
            'bot' => (bool) ($raw['bot'] ?? false),
            'score' => self::text($raw['score'] ?? '', 12),
            'legs' => self::int($raw['legs'] ?? 0, 0, 99),
            'sets' => self::int($raw['sets'] ?? 0, 0, 99),
            'average' => round(max(0, min(180, (float) ($raw['average'] ?? 0))), 2),
            'darts' => self::int($raw['darts'] ?? 0, 0, 99_999),
            'last' => isset($raw['last']) ? self::int($raw['last'], 0, 180) : null,
            'checkout' => self::text($raw['checkout'] ?? '', 40),
            'setup' => self::text($raw['setup'] ?? '', 40),
            'setupLeave' => self::text($raw['setupLeave'] ?? '', 16),
            'out' => self::outRule($raw['out'] ?? ''),
            'marks' => $marks,
        ];
        $photo = self::photo($raw['photo'] ?? '');
        if ($photo !== '') {
            $player['photo'] = $photo;
        }

        return $player;
    }

    /** @return list<array<string, mixed>> */
    private static function visits(mixed $raw, int $players): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $visits = [];
        foreach (array_slice(array_values($raw), -1500) as $visit) {
            if (!is_array($visit) || self::index($visit['player'] ?? null, $players) === null) {
                continue;
            }
            $line = [
                'player' => (int) $visit['player'],
                'darts' => self::darts($visit['darts'] ?? [], 3),
                'score' => self::int($visit['score'] ?? 0, 0, 180),
                'bust' => (bool) ($visit['bust'] ?? false),
                'checkout' => (bool) ($visit['checkout'] ?? false),
            ];
            if (is_numeric($visit['leg'] ?? null)) {
                $line['leg'] = self::int($visit['leg'], 1, 999);
            }
            if (is_numeric($visit['set'] ?? null)) {
                $line['set'] = self::int($visit['set'], 1, 99);
            }
            $visits[] = $line;
        }

        return $visits;
    }

    /** @return list<string> */
    private static function darts(mixed $raw, int $limit): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $darts = array_values(array_filter($raw, static fn ($dart): bool => is_string($dart) && preg_match(self::DART, $dart) === 1));

        return array_slice($darts, 0, $limit);
    }

    /** @return list<int> */
    private static function numbers(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        return array_slice(array_values(array_unique(array_map('intval', array_filter($raw, self::isBoardNumber(...))))), 0, 21);
    }

    private static function isBoardNumber(mixed $value): bool
    {
        return is_numeric($value) && ((int) $value === 25 || ((int) $value >= 1 && (int) $value <= 20));
    }

    private static function index(mixed $value, int $count): ?int
    {
        return is_int($value) && $value >= 0 && $value < $count ? $value : null;
    }

    private static function int(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, is_numeric($value) ? (int) $value : $min));
    }

    private static function outRule(mixed $value): string
    {
        $rule = is_scalar($value) ? (string) $value : '';

        return in_array($rule, ['double', 'straight', 'master'], true) ? $rule : '';
    }

    private static function average(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        return round(max(0, min(180, (float) $value)), 1);
    }

    /** Jen malý JPEG z telefonu. Cokoli jiného se do stránky nedostane. */
    private static function photo(mixed $value): string
    {
        if (!is_string($value) || $value === '' || strlen($value) > 20_000) {
            return '';
        }

        return preg_match('#\Adata:image/jpeg;base64,[A-Za-z0-9+/]+={0,2}\z#', $value) === 1 ? $value : '';
    }

    private static function text(mixed $value, int $max): string
    {
        if (!is_scalar($value)) {
            return '';
        }
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', trim((string) $value)) ?? '';

        return mb_substr($clean, 0, $max);
    }
}
