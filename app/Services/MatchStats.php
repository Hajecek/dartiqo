<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Z hodů zápasu (výstup MatchPayload::parse) složí log: legy se sety,
 * hráče s výsledkem a statistikami a návštěvy v pořadí, jak se házelo.
 */
final class MatchStats
{
    /**
     * @param array<string, mixed> $match
     * @return array{players: list<array<string, mixed>>, legs: list<array<string, mixed>>, visits: list<array<string, mixed>>, solo: bool}
     */
    public static function analyze(array $match): array
    {
        $config = $match['config'];
        $players = $match['players'];
        $count = count($players);
        $mode = $config['mode'];
        $x01 = $mode === 'x01';
        $scored = $x01 || $mode === 'countUp';
        $cricket = $mode === 'cricket' ? array_flip($config['cricketNumbers']) : [];

        $legs = self::legs($match);
        $setOfLeg = array_column($legs, 'set', 'leg');
        $stats = array_fill(0, $count, [
            'visits' => 0, 'darts' => 0, 'points' => 0, 'bestVisit' => 0,
            'scores60' => 0, 'scores100' => 0, 'scores140' => 0, 'scores180' => 0,
            'busts' => 0, 'checkouts' => 0, 'checkoutAttempts' => 0, 'bestCheckout' => null, 'marks' => 0,
            'first9Points' => 0, 'first9Darts' => 0,
        ]);
        $opening = [];
        $log = [];

        foreach ($match['visits'] as $index => $visit) {
            $p = $visit['player'];
            $darts = count($visit['darts']);
            $score = $visit['score'];
            $complete = $darts >= 3 || $visit['bust'] || $visit['checkout'];
            $before = null;
            if ($x01 && $visit['remaining'] !== null) {
                $before = $visit['bust'] ? $visit['remaining'] : $visit['remaining'] + $score;
            }

            $s = &$stats[$p];
            $s['visits']++;
            $s['darts'] += $darts;
            $s['points'] += $score;
            if ($complete) {
                $s['bestVisit'] = max($s['bestVisit'], $score);
            }
            if ($scored && $complete) {
                match (true) {
                    $score === 180 => $s['scores180']++,
                    $score >= 140 => $s['scores140']++,
                    $score >= 100 => $s['scores100']++,
                    $score >= 60 => $s['scores60']++,
                    default => null,
                };
            }
            if ($visit['bust']) {
                $s['busts']++;
            }
            if ($x01 && $visit['checkout']) {
                $s['checkouts']++;
                $s['bestCheckout'] = max($s['bestCheckout'] ?? 0, $score);
            }
            // Pokus o zavření = šipka hozená ve chvíli, kdy šlo zavřít jedním doublem
            if ($before !== null && self::outRule($config, $players[$p]) === 'double') {
                $left = $before;
                foreach ($visit['darts'] as $dart) {
                    if ($left === 50 || ($left >= 2 && $left <= 40 && $left % 2 === 0)) {
                        $s['checkoutAttempts']++;
                    }
                    $left -= $dart['score'];
                    if ($left <= 1) {
                        break;
                    }
                }
            }
            foreach ($visit['darts'] as $dart) {
                if (isset($cricket[$dart['segment']])) {
                    $s['marks'] += $dart['multiplier'];
                }
            }
            $key = $p . ':' . $visit['leg'];
            $opening[$key] = ($opening[$key] ?? 0) + 1;
            if ($opening[$key] <= 3) {
                $s['first9Points'] += $score;
                $s['first9Darts'] += $darts;
            }
            unset($s);

            $log[] = array_filter([
                'n' => $index + 1,
                'set' => $setOfLeg[$visit['leg']] ?? 1,
                'leg' => $visit['leg'],
                'player' => $p,
                'darts' => array_column($visit['darts'], 'label'),
                'coords' => array_filter(array_map(static fn (array $d): ?array => $d['x'] === null || $d['y'] === null ? null : [$d['x'], $d['y']], $visit['darts'])) ?: null,
                'score' => $score,
                'before' => $before,
                'after' => $x01 ? $visit['remaining'] : null,
                'bust' => $visit['bust'] ?: null,
                'checkout' => $visit['checkout'] ?: null,
                'enteredAsTotal' => $visit['total'] ?: null,
            ], static fn (mixed $value): bool => $value !== null);
        }

        $solo = count(array_unique(array_column($players, 'id'))) < 2;
        $legsWon = array_fill(0, $count, 0);
        $setsWon = array_fill(0, $count, 0);
        $bestLeg = array_fill(0, $count, null);
        foreach ($legs as $leg) {
            $w = $leg['winner'];
            if ($w === null) {
                continue;
            }
            $legsWon[$w]++;
            if ($leg['wonSet']) {
                $setsWon[$w]++;
            }
            if ($x01 && $leg['decidedBy'] === 'checkout') {
                $bestLeg[$w] = min($bestLeg[$w] ?? PHP_INT_MAX, $leg['winnerDarts']);
            }
        }

        $finished = $match['status'] === 'finished';
        $result = [];
        foreach ($players as $p => $player) {
            $s = $stats[$p];
            $outcome = match (true) {
                $solo || !$finished => 'none',
                $match['winner'] === null => 'draw',
                $match['winner'] === $p => 'won',
                default => 'lost',
            };
            $result[] = $player + [
                'position' => $p,
                'result' => $outcome,
                'legsWon' => $legsWon[$p],
                'setsWon' => $config['setsToWin'] > 1 ? $setsWon[$p] : null,
                'stats' => array_filter([
                    'visits' => $s['visits'],
                    'darts' => $s['darts'],
                    'points' => $s['points'],
                    'average' => $scored && $s['darts'] > 0 ? round($s['points'] / $s['darts'] * 3, 2) : null,
                    'first9Average' => $x01 && $s['first9Darts'] > 0 ? round($s['first9Points'] / $s['first9Darts'] * 3, 2) : null,
                    'bestVisit' => $s['bestVisit'],
                    'scores60' => $scored ? $s['scores60'] : null,
                    'scores100' => $scored ? $s['scores100'] : null,
                    'scores140' => $scored ? $s['scores140'] : null,
                    'scores180' => $scored ? $s['scores180'] : null,
                    'busts' => $x01 ? $s['busts'] : null,
                    'checkouts' => $x01 ? $s['checkouts'] : null,
                    'checkoutAttempts' => $x01 && self::outRule($config, $player) === 'double' ? $s['checkoutAttempts'] : null,
                    'checkoutPercent' => $x01 && $s['checkoutAttempts'] > 0 ? round($s['checkouts'] / $s['checkoutAttempts'] * 100, 1) : null,
                    'bestCheckout' => $s['bestCheckout'],
                    'bestLegDarts' => $bestLeg[$p],
                    'marks' => $cricket !== [] ? $s['marks'] : null,
                    'mpr' => $cricket !== [] && $s['darts'] > 0 ? round($s['marks'] / $s['darts'] * 3, 2) : null,
                ], static fn (mixed $value): bool => $value !== null),
            ];
        }

        return [
            'players' => $result,
            'legs' => array_map(static function (array $leg): array {
                unset($leg['wonSet']);

                return $leg;
            }, $legs),
            'visits' => $log,
            'solo' => $solo,
        ];
    }

    /**
     * Legy v pořadí, jak se hrály. Sety se dopočítají stejně jako v aplikaci:
     * kdo první získá legsToWin legů, bere set a počítadla legů se nulují.
     *
     * @param array<string, mixed> $match
     * @return list<array<string, mixed>>
     */
    private static function legs(array $match): array
    {
        $byLeg = [];
        foreach ($match['visits'] as $visit) {
            $byLeg[$visit['leg']][] = $visit;
        }
        foreach (array_keys($match['bullOffLegs']) as $leg) {
            $byLeg[$leg] ??= [];
        }
        ksort($byLeg);

        $count = count($match['players']);
        $playsSets = $match['config']['setsToWin'] > 1;
        $legsToWin = $match['config']['legsToWin'];
        $set = 1;
        $inSet = 0;
        $counters = array_fill(0, $count, 0);
        $last = array_key_last($byLeg);
        $legs = [];

        foreach ($byLeg as $number => $visits) {
            $inSet++;
            $checkout = null;
            foreach ($visits as $visit) {
                if ($visit['checkout']) {
                    $checkout = $visit;
                }
            }
            [$winner, $decided] = match (true) {
                $checkout !== null => [$checkout['player'], 'checkout'],
                isset($match['bullOffLegs'][$number]) => [$match['bullOffLegs'][$number], 'bullOff'],
                $number === $last && $match['winner'] !== null => [$match['winner'], 'score'],
                default => [null, null],
            };

            $darts = 0;
            $winnerDarts = 0;
            foreach ($visits as $visit) {
                $darts += count($visit['darts']);
                if ($visit['player'] === $winner) {
                    $winnerDarts += count($visit['darts']);
                }
            }

            $wonSet = false;
            if ($winner !== null) {
                $counters[$winner]++;
                $wonSet = $playsSets && $counters[$winner] >= $legsToWin;
            }
            $legs[] = [
                'leg' => $number,
                'set' => $set,
                'legInSet' => $inSet,
                'starter' => $visits[0]['player'] ?? null,
                'winner' => $winner,
                'decidedBy' => $decided,
                'darts' => $darts,
                'winnerDarts' => $winner === null ? null : $winnerDarts,
                'checkout' => $match['config']['mode'] === 'x01' && $checkout !== null ? $checkout['score'] : null,
                'wonSet' => $wonSet,
            ];
            if ($wonSet) {
                $set++;
                $inSet = 0;
                $counters = array_fill(0, $count, 0);
            }
        }

        return $legs;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $player
     */
    private static function outRule(array $config, array $player): ?string
    {
        return $player['handicap']['outRule'] ?? $config['outRule'] ?? null;
    }
}
