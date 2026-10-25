<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Online X01 pro dva hráče, na legy nebo na sety, bez double in.
 * Šipku započítá server. Bust vrací skóre na začátek návštěvy a tah končí hned.
 * Leg rozjíždějí hráči na střídačku přes celý zápas, stejně jako v aplikaci.
 */
final class OnlineX01
{
    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
    private const CODE = '/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/';
    private const OUT = ['double', 'straight', 'master'];
    /** Ranked hrají všichni stejně, ať je rating srovnatelný. */
    public const RANKED_RULES = [
        'startingScore' => 501,
        'outRule' => 'double',
        'legsToWin' => 3,
        'setsToWin' => 1,
        'format' => 'bestOf',
        'doubleIn' => false,
    ];

    /**
     * Nastavení lobby. `password` je null, když se má nechat to staré.
     *
     * @param array<string, mixed> $input
     * @return array{rules: array{startingScore:int,outRule:string,legsToWin:int,setsToWin:int,format:string,doubleIn:bool}, kind:string, visibility:string, password:?string}
     */
    public static function lobby(array $input): array
    {
        $kind = $input['kind'] ?? 'classic';
        if (!in_array($kind, ['classic', 'ranked'], true)) {
            throw new InvalidArgumentException('Neznámý typ zápasu.');
        }
        $visibility = $input['visibility'] ?? 'private';
        if (!in_array($visibility, ['public', 'private'], true)) {
            throw new InvalidArgumentException('Neznámá viditelnost lobby.');
        }
        $keep = ($input['keepPassword'] ?? false) === true;
        $password = $input['password'] ?? '';
        if (!is_string($password)) {
            throw new InvalidArgumentException('Neplatné heslo.');
        }
        $password = trim($password);
        if (strlen($password) > 64) {
            throw new InvalidArgumentException('Heslo je moc dlouhé.');
        }
        if ($kind === 'ranked') {
            return ['rules' => self::RANKED_RULES, 'kind' => 'ranked', 'visibility' => 'public', 'password' => ''];
        }

        if (($input['doubleIn'] ?? false) === true) {
            throw new InvalidArgumentException('Online zápas zatím nehraje double in.');
        }
        $score = $input['startingScore'] ?? 501;
        if (!is_int($score) || $score < 2 || $score > 1701) {
            throw new InvalidArgumentException('Výchozí skóre musí být od 2 do 1701.');
        }
        $out = $input['outRule'] ?? 'double';
        if (!is_string($out) || !in_array($out, self::OUT, true)) {
            throw new InvalidArgumentException('Neznámé zakončení.');
        }
        $legs = $input['legsToWin'] ?? 1;
        if (!is_int($legs) || $legs < 1 || $legs > 15) {
            throw new InvalidArgumentException('Počet legů musí být od 1 do 15.');
        }
        $sets = $input['setsToWin'] ?? 1;
        if (!is_int($sets) || $sets < 1 || $sets > 11) {
            throw new InvalidArgumentException('Počet setů musí být od 1 do 11.');
        }
        $format = $input['format'] ?? 'firstTo';
        if (!in_array($format, ['firstTo', 'bestOf'], true)) {
            throw new InvalidArgumentException('Neznámý formát zápasu.');
        }

        return [
            'rules' => [
                'startingScore' => $score,
                'outRule' => $out,
                'legsToWin' => $legs,
                'setsToWin' => $sets,
                'format' => $format,
                'doubleIn' => false,
            ],
            'kind' => 'classic',
            'visibility' => $visibility,
            'password' => $visibility === 'public' ? '' : ($keep ? null : $password),
        ];
    }

    public static function code(string $raw): string
    {
        $code = strtoupper(preg_replace('/\s+/', '', $raw) ?? '');
        if (preg_match(self::CODE, $code) !== 1) {
            throw new InvalidArgumentException('Neplatný kód lobby.');
        }

        return $code;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{seq:int,dartId:string,segment:int,multiplier:int,label:string,score:int}
     */
    public static function dart(array $input): array
    {
        $seq = $input['seq'] ?? null;
        if (!is_int($seq) || $seq < 0 || $seq > 4_000_000_000) {
            throw new InvalidArgumentException('Chybí pořadí hodu.');
        }
        $dartId = strtolower((string) ($input['dartId'] ?? ''));
        if (preg_match(self::UUID, $dartId) !== 1) {
            throw new InvalidArgumentException('Neplatné id šipky.');
        }
        $dart = $input['dart'] ?? null;
        if (!is_array($dart)) {
            throw new InvalidArgumentException('Neplatná šipka.');
        }
        $segment = $dart['segment'] ?? $dart['s'] ?? null;
        $multiplier = $dart['multiplier'] ?? $dart['m'] ?? null;
        $valid = is_int($segment) && is_int($multiplier) && match (true) {
            $segment === 0 => $multiplier === 1,
            $segment === 25 => $multiplier === 1 || $multiplier === 2,
            default => $segment >= 1 && $segment <= 20 && $multiplier >= 1 && $multiplier <= 3,
        };
        if (!$valid) {
            throw new InvalidArgumentException('Neplatná šipka.');
        }

        return [
            'seq' => $seq,
            'dartId' => $dartId,
            'segment' => $segment,
            'multiplier' => $multiplier,
            'label' => self::label($segment, $multiplier),
            'score' => $segment * $multiplier,
        ];
    }

    /** @param array<string, mixed> $input */
    public static function ready(array $input): bool
    {
        if (!array_key_exists('ready', $input)) {
            return true;
        }
        if (!is_bool($input['ready'])) {
            throw new InvalidArgumentException('Neplatná připravenost.');
        }

        return $input['ready'];
    }

    /**
     * Čistý stav na začátku zápasu. Hráči už v něm jsou, skóre se vynuluje na výchozí.
     *
     * @param array<string, mixed> $state
     * @param array{startingScore:int,outRule:string,legsToWin:int,doubleIn:bool} $rules
     * @return array<string, mixed>
     */
    public static function kickoff(array $state, array $rules, int $now): array
    {
        $names = [];
        foreach ($state['players'] as &$player) {
            $player['remaining'] = $rules['startingScore'];
            $player['legs'] = 0;
            $player['sets'] = 0;
            $player['darts'] = 0;
            $player['points'] = 0;
            $player['last'] = null;
            $names[] = (string) $player['name'];
        }
        unset($player);
        $state['leg'] = 1;
        $state['set'] = 1;
        $state['winner'] = null;
        $state['legWinner'] = null;
        $state['openingStarter'] = 0;
        $state['startedAt'] = $now;
        $state['lastDartId'] = null;
        $state['visit'] = [];
        $state['visits'] = [];
        $state['visitStart'] = $rules['startingScore'];
        $state['title'] = mb_substr(implode(' vs ', $names), 0, 160);

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @param array{startingScore:int,outRule:string,legsToWin:int,doubleIn:bool} $rules
     * @param array{dartId:string,segment:int,multiplier:int,label:string,score:int} $dart
     * @return array{state: array<string, mixed>, activeSlot: int, finished: bool}
     */
    public static function apply(array $state, array $rules, int $activeSlot, array $dart): array
    {
        if (!isset($state['players'][$activeSlot]) || !is_array($state['players'][$activeSlot])) {
            throw new InvalidArgumentException('Hráč na tahu chybí.');
        }
        $visit = $state['visit'] ?? [];
        if (!is_array($visit) || count($visit) >= 3) {
            throw new InvalidArgumentException('Návštěva už má tři šipky.');
        }
        if (count($state['visits'] ?? []) >= 3000) {
            throw new InvalidArgumentException('Zápas je moc dlouhý.');
        }

        $player = $state['players'][$activeSlot];
        if ($visit === []) {
            $state['visitStart'] = (int) $player['remaining'];
            $state['legWinner'] = null;
        }
        $before = (int) $player['remaining'];
        $score = (int) $dart['score'];
        $state['visit'][] = [
            'dartId' => $dart['dartId'],
            'segment' => $dart['segment'],
            'multiplier' => $dart['multiplier'],
            'label' => $dart['label'],
            'score' => $score,
        ];
        $player['darts'] = (int) $player['darts'] + 1;
        $state['lastDartId'] = $dart['dartId'];
        $state['players'][$activeSlot] = $player;

        $next = $before - $score;
        $checkout = self::isCheckout((int) $dart['multiplier'], $rules['outRule'], $score);
        if (self::isBust($score, $next, $checkout, $rules['outRule'])) {
            $player['remaining'] = (int) $state['visitStart'];
            $player['last'] = 0;
            $state['players'][$activeSlot] = $player;
            self::closeVisit($state, $activeSlot, 0, (int) $state['visitStart'], true, false);

            return ['state' => $state, 'activeSlot' => 1 - $activeSlot, 'finished' => false];
        }

        $player['remaining'] = $next;
        $state['players'][$activeSlot] = $player;
        if ($next === 0 && $checkout) {
            $visitScore = self::visitScore($state['visit']);
            $player['points'] = (int) $player['points'] + $visitScore;
            $player['last'] = $visitScore;
            $player['legs'] = (int) $player['legs'] + 1;
            $state['players'][$activeSlot] = $player;
            $state['legWinner'] = $activeSlot;
            self::closeVisit($state, $activeSlot, $visitScore, 0, false, true);
            $setsToWin = (int) ($rules['setsToWin'] ?? 1);
            $wonSet = $player['legs'] >= $rules['legsToWin'];
            if ($wonSet) {
                $player['sets'] = (int) ($player['sets'] ?? 0) + 1;
                $state['players'][$activeSlot] = $player;
                if ($player['sets'] >= $setsToWin) {
                    $state['winner'] = $activeSlot;

                    return ['state' => $state, 'activeSlot' => $activeSlot, 'finished' => true];
                }
                $state['set'] = (int) ($state['set'] ?? 1) + 1;
            }
            $state['leg'] = (int) $state['leg'] + 1;
            foreach ($state['players'] as &$reset) {
                $reset['remaining'] = $rules['startingScore'];
                if ($wonSet) {
                    $reset['legs'] = 0;
                }
            }
            unset($reset);
            $state['visitStart'] = $rules['startingScore'];

            return ['state' => $state, 'activeSlot' => ((int) $state['leg'] - 1) % 2, 'finished' => false];
        }

        if (count($state['visit']) >= 3) {
            $visitScore = self::visitScore($state['visit']);
            $player['points'] = (int) $player['points'] + $visitScore;
            $player['last'] = $visitScore;
            $state['players'][$activeSlot] = $player;
            self::closeVisit($state, $activeSlot, $visitScore, $next, false, false);

            return ['state' => $state, 'activeSlot' => 1 - $activeSlot, 'finished' => false];
        }

        return ['state' => $state, 'activeSlot' => $activeSlot, 'finished' => false];
    }

    private static function isBust(int $score, int $next, bool $checkout, string $out): bool
    {
        if ($next < 0) {
            return true;
        }
        if ($next === 0) {
            return !$checkout;
        }

        // Jednička nejde zavřít doublem ani masterem. Minutá šipka (score 0) jedničku nezkazí.
        return $next === 1 && $score > 0 && $out !== 'straight';
    }

    private static function isCheckout(int $multiplier, string $out, int $score): bool
    {
        if ($score <= 0) {
            return false;
        }

        return match ($out) {
            'straight' => true,
            'master' => $multiplier >= 2,
            default => $multiplier === 2,
        };
    }

    /** @param list<array<string, mixed>> $visit */
    private static function visitScore(array $visit): int
    {
        return array_sum(array_map(static fn (array $dart): int => (int) $dart['score'], $visit));
    }

    /** @param array<string, mixed> $state */
    private static function closeVisit(array &$state, int $slot, int $score, int $remaining, bool $bust, bool $checkout): void
    {
        $darts = [];
        foreach ($state['visit'] as $dart) {
            $darts[] = [
                'segment' => (int) $dart['segment'],
                'multiplier' => (int) $dart['multiplier'],
                'label' => (string) $dart['label'],
                'score' => (int) $dart['score'],
                'x' => null,
                'y' => null,
            ];
        }
        $state['visits'][] = [
            'player' => $slot,
            'leg' => (int) $state['leg'],
            'darts' => $darts,
            'score' => $score,
            'remaining' => $remaining,
            'bust' => $bust,
            'checkout' => $checkout,
            'total' => false,
        ];
        $state['visit'] = [];
    }

    private static function label(int $segment, int $multiplier): string
    {
        return match (true) {
            $segment === 0 => 'MISS',
            $segment === 25 => $multiplier === 2 ? 'BULL' : '25',
            default => ['', 'S', 'D', 'T'][$multiplier] . $segment,
        };
    }
}
