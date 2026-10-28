<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Zápasy jednoho hráče z tabulky matches převedené na data pro web:
 * karty her, detail zápasu, statistiky po obdobích, rekordy a milníky.
 * Vše z pohledu majitele účtu (hráč s kind = owner).
 */
final class MatchInsights
{
    private const TIMEZONE = 'Europe/Prague';
    /** Spodní hranice, popisek, barva */
    private const SCORE_BANDS = [
        [180, '180', '#f24747'], [140, '140+', '#ff8a3d'], [100, '100+', '#f5c518'], [80, '80+', '#8fd14f'],
        [60, '60+', '#4dd984'], [40, '40+', '#3ec6d4'], [20, '20+', '#5b86e5'], [0, 'pod 20', '#8e8e93'],
    ];
    private const MARK_BANDS = [
        [9, '9 značek', '#f24747'], [7, '7–8', '#ff8a3d'], [5, '5–6', '#f5c518'],
        [3, '3–4', '#4dd984'], [1, '1–2', '#5b86e5'], [0, 'bez značky', '#8e8e93'],
    ];
    private const HIT_BANDS = [[3, '3 trefy', '#4dd984'], [2, '2 trefy', '#f5c518'], [1, '1 trefa', '#5b86e5'], [0, 'vedle', '#8e8e93']];
    private const TIME_COLORS = ['Sám' => '#fefc03', 'S botem' => '#4dd984', 'S kamarády' => '#5b86e5', 'Online' => '#38bdf8'];
    private const PERIODS = ['Dnes' => 0, '7 dní' => 7, '30 dní' => 30, 'Celé období' => 9999];

    /** @var list<array<string, mixed>> */
    private array $facts;
    private DateTimeImmutable $today;

    /** @param list<array<string, mixed>> $rows řádky z matches s dekódovaným logem, od nejnovějšího */
    public function __construct(array $rows)
    {
        $this->today = new DateTimeImmutable('today', new DateTimeZone(self::TIMEZONE));
        $this->facts = array_map($this->facts(...), $rows);
    }

    public function isEmpty(): bool
    {
        return $this->facts === [];
    }

    /** @return list<array<string, mixed>> karty pro seznam her (game-row) */
    public function cards(int $limit = 0): array
    {
        $facts = $limit > 0 ? array_slice($this->facts, 0, $limit) : $this->facts;

        return array_map($this->card(...), $facts);
    }

    /** @return array<string, mixed>|null detail jednoho zápasu */
    public function detail(string $publicId): ?array
    {
        foreach ($this->facts as $fact) {
            if ($fact['id'] === $publicId) {
                return $this->card($fact);
            }
        }

        return null;
    }

    /** @return list<string> win / loss / draw posledních dohraných zápasů proti soupeři */
    public function form(int $count = 5): array
    {
        $form = [];
        foreach ($this->facts as $fact) {
            if (in_array($fact['result'], ['win', 'loss', 'draw'], true)) {
                $form[] = $fact['result'];
            }
            if (count($form) === $count) {
                break;
            }
        }

        return $form;
    }

    /** @return array<string, mixed> souhrn pro Přehled a profil */
    public function summary(): array
    {
        $done = $this->finished($this->facts);
        $totals = $this->totals($done);

        return [
            'matches' => count($done),
            'average' => $totals['average'],
            'won' => $totals['won'],
            'played' => $totals['played'],
            'winRate' => $totals['winRate'],
            'checkoutRate' => $totals['checkoutRate'],
            'hits' => $totals['hits'],
            'attempts' => $totals['attempts'],
            'scores180' => $totals['scores180'],
            'bestAverage' => $this->best($done, 'average'),
            'bestCheckout' => $this->best($done, 'bestCheckout'),
        ];
    }

    /** @return array<string, mixed> data pro stránku Statistiky (dash-data) */
    public function statistics(): array
    {
        $periods = [];
        foreach (self::PERIODS as $name => $days) {
            $periods[$name] = $this->period($name, $days);
        }

        return ['default' => '30 dní', 'periods' => $periods, 'maxDays' => self::PERIODS];
    }

    /** @return list<array{0:string,1:string,2:string}> rekordy: popisek, hodnota, ikona */
    public function records(): array
    {
        $done = $this->finished($this->facts);
        $legDarts = array_filter(array_column($done, 'bestLegDarts'));
        $won = count(array_filter($done, static fn (array $f): bool => $f['result'] === 'win'));

        return [
            ['Průměr zápasu', self::number($this->best($done, 'average')), 'target'],
            ['Nejlepší kolo', (string) ($this->best($done, 'bestVisit') ?? '–'), 'bolt'],
            ['Nejvyšší zavření', (string) ($this->best($done, 'bestCheckout') ?? '–'), 'trophy'],
            ['Šipek na leg', $legDarts === [] ? '–' : (string) min($legDarts), 'flag'],
            ['Zápasů', (string) count($done), 'chart'],
            ['Výher', (string) $won, 'check'],
        ];
    }

    /** @return array<string, mixed> data pro Přehled: grafy, série, denní výzva, poslední zápas */
    public function dashboard(): array
    {
        $done = $this->finished($this->facts);
        $month = array_values(array_filter($done, static fn (array $f): bool => $f['days'] < 30));
        $before = array_values(array_filter($done, static fn (array $f): bool => $f['days'] >= 30 && $f['days'] < 60));
        $now = $this->totals($month);
        $prev = $this->totals($before);
        $all = $this->totals($done);
        $oldest = array_reverse($done);
        $scored = array_values(array_filter($oldest, static fn (array $f): bool => $f['average'] !== null));
        $versus = array_values(array_filter($oldest, static fn (array $f): bool => in_array($f['result'], ['win', 'loss', 'draw'], true)));
        $closing = array_values(array_filter($oldest, static fn (array $f): bool => $f['attempts'] > 0));

        $winLine = [];
        $won = 0;
        foreach ($versus as $i => $f) {
            $won += $f['result'] === 'win' ? 1 : 0;
            $winLine[] = (int) round($won / ($i + 1) * 100);
        }

        $trend = [];
        for ($ago = 29; $ago >= 0; $ago--) {
            $day = $this->totals(array_values(array_filter($month, static fn (array $f): bool => $f['days'] === $ago)))['average'];
            $trend[] = $day === null ? null : round($day, 1);
        }

        $playedDays = array_flip(array_unique(array_column($done, 'date')));
        $week = [];
        for ($ago = 6; $ago >= 0; $ago--) {
            $date = $this->today->modify('-' . $ago . ' day');
            $week[] = [
                'label' => ['NE', 'PO', 'ÚT', 'ST', 'ČT', 'PÁ', 'SO'][(int) $date->format('w')],
                'trained' => isset($playedDays[$date->format('Y-m-d')]),
                'today' => $ago === 0,
            ];
        }
        $streak = 0;
        $start = isset($playedDays[$this->today->format('Y-m-d')]) ? 0 : 1;
        while (isset($playedDays[$this->today->modify('-' . ($start + $streak) . ' day')->format('Y-m-d')])) {
            $streak++;
        }
        $thisMonth = count(array_filter(array_keys($playedDays), fn (string $d): bool => str_starts_with($d, $this->today->format('Y-m'))));

        $tasksDone = 0;
        foreach (array_keys($playedDays) as $date) {
            $tasksDone += count(array_filter($this->dailyTasks($date), static fn (array $t): bool => $t[2]));
        }
        $xp = $tasksDone * 50;
        $level = intdiv($xp, 1000) + 1;

        $delta = static fn (?float $a, ?float $b, string $unit = ''): string => $a === null || $b === null ? '–' : sprintf('%+.1f', $a - $b) . $unit;

        return [
            'average' => $all['average'],
            'averageDelta' => $delta($now['average'], $prev['average']),
            'averageLine' => self::line(array_map(static fn (array $f): float => round((float) $f['average'], 1), array_slice($scored, -24))),
            'kpis' => [
                'average' => ['value' => self::number($now['average']), 'delta' => $delta($now['average'], $prev['average']), 'line' => self::line(array_map(static fn (array $f): float => round((float) $f['average'], 1), array_slice($scored, -12)))],
                'wins' => ['value' => $now['played'] > 0 ? $now['winRate'] . ' %' : '–', 'rate' => $now['winRate'], 'label' => $now['won'] . '/' . $now['played'], 'delta' => $prev['played'] > 0 && $now['played'] > 0 ? sprintf('%+d %%', $now['winRate'] - $prev['winRate']) : '–', 'line' => self::line(array_slice($winLine, -12))],
                'closing' => ['value' => $now['attempts'] > 0 ? $now['checkoutRate'] . ' %' : '–', 'rate' => $now['checkoutRate'], 'label' => $now['hits'] . '/' . $now['attempts'], 'delta' => $prev['attempts'] > 0 && $now['attempts'] > 0 ? sprintf('%+d %%', $now['checkoutRate'] - $prev['checkoutRate']) : '–', 'bars' => array_map(static fn (array $f): int => (int) round($f['hits'] / $f['attempts'] * 100), array_slice($closing, -12))],
                'max' => ['value' => (string) $now['scores180'], 'delta' => $before === [] ? '–' : sprintf('%+d', $now['scores180'] - $prev['scores180']), 'bars' => array_map(static fn (array $f): int => $f['scores180'], array_slice($scored, -12))],
            ],
            'trend' => $trend,
            'monthAverage' => $now['average'],
            'week' => $week,
            'streak' => $streak,
            'longestStreak' => $this->longestStreak($done),
            'monthDays' => $thisMonth,
            'playedToday' => isset($playedDays[$this->today->format('Y-m-d')]),
            'tasks' => $this->dailyTasks($this->today->format('Y-m-d')),
            'xp' => $xp % 1000,
            'level' => $level,
            'rank' => match (true) {
                $level >= 10 => 'Expert',
                $level >= 5 => 'Pokročilý',
                default => 'Nováček',
            },
            'last' => $done === [] ? null : $this->card($done[0]),
        ];
    }

    /** @return list<array{0:string,1:string,2:bool}> úkoly denní výzvy pro den Y-m-d: název, popis, splněno */
    private function dailyTasks(string $date): array
    {
        $day = array_filter($this->finished($this->facts), static fn (array $f): bool => $f['date'] === $date);
        $max = array_sum(array_column($day, 'scores180')) > 0;
        $checkouts = array_merge([], ...array_column($day, 'checkouts'));
        $checkout = $checkouts !== [] && max($checkouts) >= 40;
        $clock = in_array('aroundClock', array_column($day, 'mode'), true);

        return [
            ['Tři T20 v kole', $max ? 'Hotovo · zásah 180' : 'Hoď v zápase maximum 180', $max],
            ['Checkout 40+', $checkout ? 'Hotovo · zavření ' . max($checkouts) : 'Zavři leg ze 40 a víc', $checkout],
            ['Kolem hodin', $clock ? 'Hotovo' : 'Projdi 1–20 a bull', $clock],
        ];
    }

    /** @param list<float|int> $values @return list<float|int> řada pro čárový graf, aspoň dva body */
    private static function line(array $values): array
    {
        return count($values) === 1 ? [$values[0], $values[0]] : $values;
    }

    /** @return list<array{0:string,1:bool}> */
    public function milestones(): array
    {
        $done = $this->finished($this->facts);
        $won = count(array_filter($done, static fn (array $f): bool => $f['result'] === 'win'));
        $checkouts = array_merge([], ...array_column($done, 'checkouts'));

        return [
            ['První zápas', $done !== []],
            ['První výhra', $won >= 1],
            ['Maximum 180', array_sum(array_column($done, 'scores180')) > 0],
            ['Zavření 100+', $checkouts !== [] && max($checkouts) >= 100],
            ['20 zápasů', count($done) >= 20],
            ['50 výher', $won >= 50],
            ['7 dní v řadě', $this->longestStreak($done) >= 7],
            ['Zavření 170', in_array(170, $checkouts, true)],
        ];
    }

    // ---------------------------------------------------------------- zápas

    /** @return array<string, mixed> */
    private function facts(array $row): array
    {
        $log = $row['log'];
        $players = $log['players'];
        $me = 0;
        foreach ($players as $index => $player) {
            if ($player['kind'] === 'owner') {
                $me = $index;
                break;
            }
        }
        $config = $log['config'];
        $mode = $config['mode'];
        $scored = in_array($mode, ['x01', 'countUp'], true);
        $opponents = array_values(array_filter($players, static fn (array $p): bool => $p['position'] !== $me));
        $versus = $opponents !== [] && empty($row['is_solo']);
        $mine = $players[$me];

        $started = (new DateTimeImmutable((string) $row['started_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(self::TIMEZONE));
        $online = ($row['kind'] ?? '') === 'online';
        $result = match (true) {
            $online && $row['status'] === 'abandoned' => 'quit',
            $row['status'] !== 'finished' => 'none',
            !$versus => 'train',
            $mine['result'] === 'won' => 'win',
            $mine['result'] === 'lost' => 'loss',
            default => 'draw',
        };

        $segments = array_fill_keys(range(1, 20), [0, 0, 0]);
        $bull = [0, 0];
        $misses = 0;
        $bands = array_fill(0, count($this->bandsFor($mode)), 0);
        $doubles = [];
        $ranges = [0, 0, 0, 0];
        $checkouts = [];
        $cricket = array_flip($config['cricketNumbers'] ?? []);
        $myVisits = [];

        foreach ($log['visits'] as $visit) {
            if ($visit['player'] !== $me) {
                continue;
            }
            $myVisits[] = $visit;
            $left = $visit['before'] ?? null;
            $marks = 0;
            $hits = 0;
            foreach ($visit['darts'] as $label) {
                [$segment, $multiplier, $score] = self::dart($label);
                if ($segment === 0) {
                    $misses++;
                } elseif ($segment === 25) {
                    $bull[$multiplier - 1]++;
                } else {
                    $segments[$segment][$multiplier - 1]++;
                }
                if (isset($cricket[$segment])) {
                    $marks += $multiplier;
                }
                if ($segment !== 0) {
                    $hits++;
                }
                if ($left !== null && $mode === 'x01') {
                    $target = $left === 50 ? 'BULL' : ($left >= 2 && $left <= 40 && $left % 2 === 0 ? 'D' . ($left / 2) : null);
                    if ($target !== null) {
                        $doubles[$target] ??= [0, 0];
                        $doubles[$target][1]++;
                        if ($label === $target) {
                            $doubles[$target][0]++;
                        }
                    }
                    $left -= $score;
                    if ($left <= 1) {
                        $left = null;
                    }
                }
            }
            $complete = count($visit['darts']) >= 3 || !empty($visit['bust']) || !empty($visit['checkout']);
            if ($complete) {
                $value = match ($mode) {
                    'cricket' => $marks,
                    'aroundClock' => $hits,
                    default => $visit['score'],
                };
                foreach ($this->bandsFor($mode) as $i => [$floor]) {
                    if ($value >= $floor) {
                        $bands[$i]++;
                        break;
                    }
                }
            }
            if ($mode === 'x01' && !empty($visit['checkout'])) {
                $checkouts[] = $visit['score'];
                $ranges[match (true) {
                    $visit['score'] <= 40 => 0,
                    $visit['score'] <= 80 => 1,
                    $visit['score'] <= 100 => 2,
                    default => 3,
                }]++;
            }
        }

        $stats = $mine['stats'];
        $hasBot = in_array('bot', array_column($opponents, 'kind'), true);
        // Výhra po odchodu soupeře není dohraný X01. Ty šipky aplikace do průměru nebere.
        $forfeit = self::awardedByForfeit($row, $log);
        $counts = $scored && !$forfeit;

        return [
            'id' => (string) $row['public_id'],
            'row' => $row,
            'log' => $log,
            'me' => $me,
            'mine' => $mine,
            'opponents' => $opponents,
            'mode' => $mode,
            'scored' => $scored,
            'versus' => $versus,
            'result' => $result,
            'started' => $started,
            'date' => $started->format('Y-m-d'),
            'days' => (int) $this->today->diff($started->setTime(0, 0))->days,
            'finished' => $row['status'] === 'finished',
            'online' => $online,
            'category' => $online ? 'Online' : (!$versus ? 'Sám' : ($hasBot ? 'S botem' : 'S kamarády')),
            'seconds' => (int) $row['play_seconds'],
            'myVisits' => $myVisits,
            'points' => $counts ? (int) ($stats['points'] ?? 0) : 0,
            'darts' => $counts ? (int) ($stats['darts'] ?? 0) : 0,
            'average' => $counts ? ($stats['average'] ?? null) : null,
            'mpr' => $stats['mpr'] ?? null,
            'first9' => $forfeit ? [0, 0] : $this->first9($log['visits'], $me),
            'scores180' => (int) ($stats['scores180'] ?? 0),
            'bestVisit' => (int) ($stats['bestVisit'] ?? 0),
            'bestCheckout' => $stats['bestCheckout'] ?? null,
            'bestLegDarts' => $stats['bestLegDarts'] ?? null,
            'hits' => (int) ($stats['checkouts'] ?? 0),
            'attempts' => (int) ($stats['checkoutAttempts'] ?? 0),
            'legsWon' => (int) ($mine['legsWon'] ?? 0),
            'legsPlayed' => count(array_filter($log['legs'], static fn (array $leg): bool => $leg['winner'] !== null)),
            'board' => ['segments' => $segments, 'bull' => $bull],
            'misses' => $misses,
            'bands' => $bands,
            'doubles' => $doubles,
            'ranges' => $ranges,
            'checkouts' => $checkouts,
        ];
    }

    /** @return array<string, mixed> karta ve tvaru, který čte game-row a detail zápasu */
    private function card(array $f): array
    {
        $log = $f['log'];
        $config = $log['config'];
        $mine = $f['mine'];
        $opponents = $f['opponents'];
        $stats = $mine['stats'];
        $sets = ($config['setsToWin'] ?? 1) > 1;

        $myScore = $sets ? (int) ($mine['setsWon'] ?? 0) : $f['legsWon'];
        $oppScore = 0;
        foreach ($opponents as $opponent) {
            $oppScore = max($oppScore, $sets ? (int) ($opponent['setsWon'] ?? 0) : (int) ($opponent['legsWon'] ?? 0));
        }
        $bestOpp = null;
        foreach ($opponents as $opponent) {
            $value = $f['mode'] === 'cricket' ? ($opponent['stats']['mpr'] ?? null) : ($opponent['stats']['average'] ?? null);
            if ($value !== null && ($bestOpp === null || $value > $bestOpp[1])) {
                $bestOpp = [$opponent['name'], $value];
            }
        }

        $matchAverage = $stats['average'] ?? $f['average'];
        $soloScore = match ($f['mode']) {
            'countUp' => (string) ($stats['points'] ?? 0),
            'aroundClock' => (string) ($stats['darts'] ?? 0),
            'cricket' => self::number($f['mpr'], 2),
            default => self::number($matchAverage),
        };
        $label = match ($f['result']) {
            'win' => 'Výhra',
            'loss' => 'Prohra',
            'draw' => 'Remíza',
            'train' => 'Dohráno',
            'quit' => 'Vzdáno',
            default => 'Nedohráno',
        };
        $myValue = $f['mode'] === 'cricket' ? self::number($f['mpr'], 2) : ($f['scored'] ? self::number($matchAverage) : (string) ($stats['darts'] ?? 0));

        return [
            'id' => $f['id'],
            'days' => $f['days'],
            'mode' => match ($f['mode']) {
                'x01' => (string) ($config['startingScore'] ?? 501),
                'cricket' => 'Cricket',
                'aroundClock' => 'Kolem hodin',
                default => 'Count Up',
            },
            'kind' => $f['mode'],
            'online' => $f['online'],
            'tone' => match (true) {
                $f['mode'] === 'cricket' => 'cricket',
                !$f['versus'] => 'train',
                $f['category'] === 'S botem' => 'local',
                default => 'friend',
            },
            'title' => $this->opponentLine($f),
            'meta' => $this->dayLabel($f['started'], $f['days']) . ' · ' . $f['started']->format('G:i') . ' · ' . $this->ruleLine($config)
                . ($f['online'] && $f['seconds'] > 0 ? ' · ' . self::played($f['seconds']) : ''),
            'score' => $f['versus'] ? $myScore . ' : ' . $oppScore : $soloScore,
            'avg' => $myValue,
            'oppAvg' => $bestOpp === null ? '' : self::number($bestOpp[1], $f['mode'] === 'cricket' ? 2 : 1),
            'result' => $f['result'],
            'label' => $label,
            'format' => (string) $log['title'],
            'place' => $f['online'] ? 'Online' : match (true) {
                !$f['versus'] => 'Sám',
                $f['category'] === 'S botem' => 'S botem',
                count($opponents) > 1 => 'S kamarády',
                default => 'S kamarádem',
            },
            'misses' => $f['misses'],
            'unit' => 'kol',
            'chartTitle' => match ($f['mode']) {
                'cricket' => 'Značky na kolo',
                'aroundClock' => 'Trefy na kolo',
                default => 'Skórování',
            },
            'chartNote' => 'Rozložení tvých kol v tomhle zápase.',
            'board' => $f['board'],
            'bands' => $this->bandRows($f['mode'], $f['bands']),
            'kpis' => $this->kpis($f, $bestOpp),
            'stepsTitle' => $f['mode'] === 'countUp' ? 'Hra' : ($sets ? 'Sety a legy' : 'Legy'),
            'steps' => $this->steps($f),
            'doubles' => $this->doubleRows($f['doubles'], 6),
        ];
    }

    /** @return list<array{0:string,1:string,2:string}> */
    private function kpis(array $f, ?array $bestOpp): array
    {
        $stats = $f['mine']['stats'];
        $minutes = max(1, (int) round($f['seconds'] / 60)) . ' min';
        $legDarts = $this->wonLegDarts($f);
        $opp = $f['versus'] && $bestOpp !== null ? [['Soupeř', self::number($bestOpp[1], $f['mode'] === 'cricket' ? 2 : 1), $bestOpp[0]]] : [];

        return match ($f['mode']) {
            'cricket' => array_merge(
                [['MPR', self::number($f['mpr'], 2), 'značek na kolo']],
                $opp,
                [
                    ['Značky', (string) ($stats['marks'] ?? 0), 'za celý zápas'],
                    ['Nejlepší kolo', (string) max([0, ...$this->markCounts($f)]), 'značek najednou'],
                    ['Šipky', (string) ($stats['darts'] ?? 0), 'hozeno celkem'],
                    ['Čas', $minutes, 'u terče'],
                ]
            ),
            'aroundClock' => [
                ['Šipky', (string) ($stats['darts'] ?? 0), 'na celé kolo'],
                ['Trefy', $this->hitRate($f) . ' %', 'šipek v cíli'],
                ['Kola', (string) ($stats['visits'] ?? 0), 'návštěv u terče'],
                ['Čas', $minutes, 'u terče'],
            ],
            'countUp' => array_merge(
                [['Průměr', self::number($stats['average'] ?? $f['average']), 'na 3 šipky']],
                $opp,
                [
                    ['Body', (string) ($stats['points'] ?? 0), 'celkem'],
                    ['Nejlepší kolo', (string) ($stats['bestVisit'] ?? 0), 'nejvíc najednou'],
                    ['180', (string) ($stats['scores180'] ?? 0), 'maxim v zápase'],
                    ['Čas', $minutes, 'u terče'],
                ]
            ),
            default => array_merge(
                [['Průměr', self::number($stats['average'] ?? $f['average']), 'na 3 šipky']],
                $opp,
                [
                    ['Prvních 9', self::number($stats['first9Average'] ?? null), 'úvodní tři kola'],
                    ['Zavírání', $f['attempts'] > 0 ? (int) round($f['hits'] / $f['attempts'] * 100) . ' %' : '–', $f['hits'] . ' z ' . $f['attempts'] . ' na double'],
                    ['180', (string) ($stats['scores180'] ?? 0), ($stats['scores180'] ?? 0) > 0 ? 'maxim v zápase' : 'žádné maximum'],
                    ['Nejvyšší zavření', (string) ($stats['bestCheckout'] ?? '–'), 'nejvyšší checkout'],
                    ['Šipek na leg', $legDarts === null ? '–' : self::number($legDarts), 've vyhraných legách'],
                    ['Nejlepší kolo', (string) ($stats['bestVisit'] ?? 0), 'nejvíc najednou'],
                    ['Přehozy', (string) ($stats['busts'] ?? 0), 'kol přes zbytek'],
                    ['Čas', $minutes, 'u terče'],
                ]
            ),
        };
    }

    /** @return list<array<string, string>> */
    private function steps(array $f): array
    {
        $log = $f['log'];
        $me = $f['me'];
        $sets = ($log['config']['setsToWin'] ?? 1) > 1;
        $names = array_column($log['players'], 'name', 'position');
        $steps = [];

        foreach ($log['legs'] as $leg) {
            $darts = [];
            $myPoints = 0;
            foreach ($log['visits'] as $visit) {
                if ($visit['leg'] !== $leg['leg']) {
                    continue;
                }
                $darts[$visit['player']] = ($darts[$visit['player']] ?? 0) + count($visit['darts']);
                if ($visit['player'] === $me) {
                    $myPoints += $visit['score'];
                }
            }
            $myDarts = $darts[$me] ?? 0;
            unset($darts[$me]);
            // Proti vítězi legu jeho šipky, jinak nejvytrvalejší soupeř
            $oppDarts = $leg['winner'] !== null && $leg['winner'] !== $me ? ($darts[$leg['winner']] ?? 0) : ($darts === [] ? 0 : max($darts));
            $result = match (true) {
                $leg['winner'] === null => 'none',
                $leg['winner'] === $me => count($log['players']) > 1 ? 'win' : 'train',
                default => 'loss',
            };
            $meta = [$myDarts . ' šipek'];
            if ($f['scored'] && $myDarts > 0) {
                $meta[] = 'Ø ' . self::number($myPoints / $myDarts * 3);
            }
            if ($leg['winner'] === $me && $leg['checkout'] !== null) {
                $meta[] = 'zavření ' . $leg['checkout'];
            } elseif ($leg['winner'] !== null && $leg['winner'] !== $me) {
                $meta[] = 'vyhrál ' . ($names[$leg['winner']] ?? 'soupeř');
            }
            if ($leg['decidedBy'] === 'bullOff') {
                $meta[] = 'rozhoz na střed';
            }
            $steps[] = [
                'title' => ($sets ? 'Set ' . $leg['set'] . ' · ' : '') . 'Leg ' . ($sets ? $leg['legInSet'] : $leg['leg']),
                'meta' => implode(' · ', $meta),
                'result' => $result,
                'label' => match ($result) {
                    'win' => 'Výhra',
                    'loss' => 'Prohra',
                    'train' => 'Dohráno',
                    default => 'Nedohráno',
                },
                'score' => count($log['players']) > 1 ? $myDarts . ' : ' . $oppDarts : (string) $myDarts,
            ];
        }

        return $steps;
    }

    // ------------------------------------------------------------- období

    /** @return array<string, mixed> */
    private function period(string $name, int $days): array
    {
        $done = $this->finished($this->facts);
        $inPeriod = array_values(array_filter($done, static fn (array $f): bool => $days === 0 ? $f['days'] === 0 : $f['days'] < max(1, $days)));
        $totals = $this->totals($inPeriod);

        $previous = [];
        if ($days > 0 && $days < 9999) {
            $previous = array_filter($done, static fn (array $f): bool => $f['days'] >= $days && $f['days'] < 2 * $days);
        } elseif ($days === 0) {
            $previous = array_filter($done, static fn (array $f): bool => $f['days'] === 1);
        }
        $before = $this->totals(array_values($previous))['average'];
        $delta = $totals['average'] !== null && $before !== null ? sprintf('%+.1f', $totals['average'] - $before) : '–';

        $length = match (true) {
            $days === 0 => 1,
            $days < 9999 => $days,
            default => min(365, max(1, ($done === [] ? 0 : max(array_column($done, 'days'))) + 1)),
        };
        $chart = [];
        for ($ago = $length - 1; $ago >= 0; $ago--) {
            $day = $this->totals(array_values(array_filter($inPeriod, static fn (array $f): bool => $f['days'] === $ago)));
            $chart[] = $day['average'] === null ? null : round($day['average'], 1);
        }

        $segments = array_fill_keys(range(1, 20), [0, 0, 0]);
        $bull = [0, 0];
        $bands = array_fill(0, count(self::SCORE_BANDS), 0);
        $time = array_fill_keys(array_keys(self::TIME_COLORS), 0);
        $misses = 0;
        $legsWon = 0;
        $legsPlayed = 0;
        foreach ($inPeriod as $f) {
            foreach ($f['board']['segments'] as $number => $parts) {
                foreach ($parts as $i => $count) {
                    $segments[$number][$i] += $count;
                }
            }
            $bull[0] += $f['board']['bull'][0];
            $bull[1] += $f['board']['bull'][1];
            $misses += $f['misses'];
            if ($f['scored']) {
                foreach ($f['bands'] as $i => $count) {
                    $bands[$i] += $count;
                }
            }
            $time[$f['category']] += $f['seconds'];
            if ($f['versus']) {
                $legsWon += $f['legsWon'];
                $legsPlayed += $f['legsPlayed'];
            }
        }
        foreach ($this->facts as $f) {
            if ($f['finished'] || !$f['online'] || $f['seconds'] <= 0) {
                continue;
            }
            $inRange = $days === 0 ? $f['days'] === 0 : $f['days'] < max(1, $days);
            if ($inRange) {
                $time[$f['category']] += $f['seconds'];
            }
        }
        foreach ($time as $label => $seconds) {
            $time[$label] = (int) round($seconds / 60);
        }

        $form = [];
        foreach ($inPeriod as $f) {
            if (in_array($f['result'], ['win', 'loss'], true) && count($form) < 5) {
                $form[] = $f['result'] === 'win';
            }
        }

        return [
            'average' => self::number($totals['average']),
            'delta' => $delta,
            'deltaNote' => match (true) {
                $days === 0 => 'celkový průměr · oproti včerejšku',
                $days < 9999 => 'celkový průměr · oproti předchozím ' . $days . ' dnům',
                default => 'celkový průměr od první hry',
            },
            'kpis' => [
                ['Prvních 9', self::number($totals['first9']), 'průměr úvodních tří kol'],
                ['Nejlepší kolo', (string) ($this->best($inPeriod, 'bestVisit') ?? '–'), 'nejvíc bodů najednou'],
                ['Legy', $legsWon . '/' . $legsPlayed, 'vyhrané / odehrané'],
                ['180', (string) $totals['scores180'], 'kol s maximem'],
            ],
            'chart' => $chart,
            'bands' => $this->bandRows('x01', $bands),
            'visits' => array_sum($bands),
            'time' => array_map(static fn (string $label): array => [$label, $time[$label], self::TIME_COLORS[$label]], array_keys(self::TIME_COLORS)),
            'board' => ['segments' => $segments, 'bull' => $bull],
            'misses' => $misses,
            'wins' => ['rate' => $totals['winRate'], 'won' => $totals['won'], 'played' => $totals['played'], 'form' => array_reverse($form)],
            'closing' => $this->closing($inPeriod, $totals),
        ];
    }

    /** @return array<string, mixed> */
    private function closing(array $facts, array $totals): array
    {
        $doubles = [];
        $ranges = [0, 0, 0, 0];
        $checkouts = [];
        foreach ($facts as $f) {
            foreach ($f['doubles'] as $label => [$hits, $attempts]) {
                $doubles[$label] ??= [0, 0];
                $doubles[$label][0] += $hits;
                $doubles[$label][1] += $attempts;
            }
            foreach ($f['ranges'] as $i => $count) {
                $ranges[$i] += $count;
            }
            array_push($checkouts, ...$f['checkouts']);
        }
        $rows = $this->doubleRows($doubles, 8);
        $rated = array_values(array_filter($rows, static fn (array $r): bool => $r[2] >= 2)) ?: $rows;
        usort($rated, static fn (array $a, array $b): int => $b[1] / max(1, $b[2]) <=> $a[1] / max(1, $a[2]));

        return [
            'rate' => $totals['checkoutRate'],
            'hits' => $totals['hits'],
            'attempts' => $totals['attempts'],
            'count' => count($checkouts),
            'highest' => $checkouts === [] ? '—' : max($checkouts),
            'average' => $checkouts === [] ? '—' : (int) round(array_sum($checkouts) / count($checkouts)),
            'best' => $rated[0] ?? ['—', 0, 0],
            'worst' => $rated !== [] ? $rated[count($rated) - 1] : ['—', 0, 0],
            'doubles' => $rows,
            'ranges' => [['2–40', $ranges[0]], ['41–80', $ranges[1]], ['81–100', $ranges[2]], ['101+', $ranges[3]]],
        ];
    }

    /** @return array{average:?float,first9:?float,won:int,played:int,winRate:int,hits:int,attempts:int,checkoutRate:int,scores180:int} */
    private function totals(array $facts): array
    {
        $points = 0;
        $darts = 0;
        $f9Points = 0;
        $f9Darts = 0;
        $won = 0;
        $played = 0;
        $hits = 0;
        $attempts = 0;
        $s180 = 0;
        foreach ($facts as $f) {
            $points += $f['points'];
            $darts += $f['darts'];
            if ($f['mode'] === 'x01') {
                $f9Points += $f['first9'][0];
                $f9Darts += $f['first9'][1];
            }
            if (in_array($f['result'], ['win', 'loss', 'draw'], true)) {
                $played++;
                $won += $f['result'] === 'win' ? 1 : 0;
            }
            $hits += $f['hits'];
            $attempts += $f['attempts'];
            $s180 += $f['scores180'];
        }

        return [
            'average' => $darts > 0 ? $points / $darts * 3 : null,
            'first9' => $f9Darts > 0 ? $f9Points / $f9Darts * 3 : null,
            'won' => $won,
            'played' => $played,
            'winRate' => $played > 0 ? (int) round($won / $played * 100) : 0,
            'hits' => $hits,
            'attempts' => $attempts,
            'checkoutRate' => $attempts > 0 ? (int) round($hits / $attempts * 100) : 0,
            'scores180' => $s180,
        ];
    }

    // ------------------------------------------------------------- pomocné

    /** @return array{0:int,1:int} body a šipky prvních tří kol v každém legu */
    private function first9(array $visits, int $me): array
    {
        $seen = [];
        $points = 0;
        $darts = 0;
        foreach ($visits as $visit) {
            if ($visit['player'] !== $me) {
                continue;
            }
            $seen[$visit['leg']] = ($seen[$visit['leg']] ?? 0) + 1;
            if ($seen[$visit['leg']] <= 3) {
                $points += $visit['score'];
                $darts += count($visit['darts']);
            }
        }

        return [$points, $darts];
    }

    private function wonLegDarts(array $f): ?float
    {
        $darts = [];
        foreach ($f['log']['legs'] as $leg) {
            if ($leg['winner'] === $f['me'] && $leg['decidedBy'] === 'checkout') {
                $darts[] = $leg['winnerDarts'];
            }
        }

        return $darts === [] ? null : array_sum($darts) / count($darts);
    }

    /** @return list<int> */
    private function markCounts(array $f): array
    {
        $cricket = array_flip($f['log']['config']['cricketNumbers'] ?? []);

        return array_map(static function (array $visit) use ($cricket): int {
            $marks = 0;
            foreach ($visit['darts'] as $label) {
                [$segment, $multiplier] = self::dart($label);
                $marks += isset($cricket[$segment]) ? $multiplier : 0;
            }

            return $marks;
        }, $f['myVisits']);
    }

    private function hitRate(array $f): int
    {
        $darts = 0;
        $hits = 0;
        foreach ($f['myVisits'] as $visit) {
            foreach ($visit['darts'] as $label) {
                $darts++;
                $hits += $label === 'MISS' ? 0 : 1;
            }
        }

        return $darts > 0 ? (int) round($hits / $darts * 100) : 0;
    }

    private function opponentLine(array $f): string
    {
        if (!$f['versus']) {
            return 'Sólo · ' . match ($f['mode']) {
                'x01' => (string) ($f['log']['config']['startingScore'] ?? 501),
                'cricket' => 'Cricket',
                'aroundClock' => 'Kolem hodin',
                default => 'Count Up',
            };
        }

        return implode(' · ', array_map(static function (array $p): string {
            if ($p['kind'] === 'bot') {
                return $p['name'] . (isset($p['bot']['level']) ? ' (bot ' . $p['bot']['level'] . ')' : ' (bot)');
            }

            return $p['kind'] === 'ghost' ? $p['name'] . ' (ghost)' : $p['name'];
        }, $f['opponents']));
    }

    private function ruleLine(array $config): string
    {
        return match ($config['mode']) {
            'x01' => ['double' => 'Double out', 'straight' => 'Straight out', 'master' => 'Master out'][$config['outRule'] ?? 'double'] ?? 'Double out',
            'cricket' => !empty($config['cricketNoScore']) ? 'bez bodů' : 's body',
            'aroundClock' => ['any' => 'libovolný zásah', 'doubles' => 'jen doubly', 'triples' => 'jen triply'][$config['clockStyle'] ?? 'any'] ?? '',
            default => ($config['countUpRounds'] ?? 10) . ' kol',
        };
    }

    private function dayLabel(DateTimeImmutable $date, int $days): string
    {
        return match ($days) {
            0 => 'Dnes',
            1 => 'Včera',
            default => $date->format('j. n.'),
        };
    }

    /** @return list<array{0:int,1:string,2:string}> */
    private function bandsFor(string $mode): array
    {
        return match ($mode) {
            'cricket' => self::MARK_BANDS,
            'aroundClock' => self::HIT_BANDS,
            default => self::SCORE_BANDS,
        };
    }

    /** @return list<array{0:string,1:int,2:string}> */
    private function bandRows(string $mode, array $counts): array
    {
        return array_map(static fn (array $band, int $i): array => [$band[1], $counts[$i] ?? 0, $band[2]], $this->bandsFor($mode), array_keys($this->bandsFor($mode)));
    }

    /** @return list<array{0:string,1:int,2:int}> nejčastější doubly: název, zavření, pokusy */
    private function doubleRows(array $doubles, int $limit): array
    {
        $rows = [];
        foreach ($doubles as $label => [$hits, $attempts]) {
            $rows[] = [$label === 'BULL' ? 'Bull' : $label, $hits, $attempts];
        }
        usort($rows, static fn (array $a, array $b): int => $b[2] <=> $a[2] ?: $b[1] <=> $a[1]);

        return array_slice($rows, 0, $limit);
    }

    private function finished(array $facts): array
    {
        return array_values(array_filter($facts, static fn (array $f): bool => $f['finished']));
    }

    /**
     * Zápas, který web označil jako výhru, protože soupeř odešel.
     * Není v něm checkout ani limit šipek, takže do tříšipkového průměru nepatří.
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed> $log
     */
    private static function awardedByForfeit(array $row, array $log): bool
    {
        if (($log['forfeit'] ?? false) === true) {
            return true;
        }
        $config = is_array($log['config'] ?? null) ? $log['config'] : [];
        if (($config['mode'] ?? '') !== 'x01' || ($config['dartLimit'] ?? null) !== null) {
            return false;
        }
        if (($row['status'] ?? '') !== 'finished' || ($log['winner'] ?? null) === null) {
            return false;
        }
        $legs = is_array($log['legs'] ?? null) ? $log['legs'] : [];
        if ($legs === []) {
            return false;
        }
        foreach ($legs as $leg) {
            if (in_array($leg['decidedBy'] ?? null, ['checkout', 'bullOff'], true)) {
                return false;
            }
        }

        return true;
    }

    private function best(array $facts, string $key): int|float|null
    {
        $values = array_filter(array_column($facts, $key), static fn (mixed $v): bool => $v !== null);

        return $values === [] ? null : max($values);
    }

    private function longestStreak(array $facts): int
    {
        $dates = array_unique(array_column($facts, 'date'));
        sort($dates);
        $best = 0;
        $run = 0;
        $previous = null;
        foreach ($dates as $date) {
            $run = $previous !== null && (new DateTimeImmutable($previous))->modify('+1 day')->format('Y-m-d') === $date ? $run + 1 : 1;
            $best = max($best, $run);
            $previous = $date;
        }

        return $best;
    }

    /** @return array{0:int,1:int,2:int} segment, násobek, body */
    private static function dart(string $label): array
    {
        return match (true) {
            $label === 'MISS' => [0, 1, 0],
            $label === 'BULL' => [25, 2, 50],
            $label === '25' => [25, 1, 25],
            default => [(int) substr($label, 1), ['S' => 1, 'D' => 2, 'T' => 3][$label[0]] ?? 1, (int) substr($label, 1) * (['S' => 1, 'D' => 2, 'T' => 3][$label[0]] ?? 1)],
        };
    }

    private static function played(int $seconds): string
    {
        if ($seconds < 60) {
            return '<1 min';
        }

        return (int) round($seconds / 60) . ' min';
    }

    private static function number(int|float|null $value, int $decimals = 1): string
    {
        return $value === null ? '–' : number_format((float) $value, $decimals, '.', '');
    }
}
