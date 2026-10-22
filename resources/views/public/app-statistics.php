<?php
require_once __DIR__ . '/../partials/ui.php';
$dailyAverages = static function (int $days, float $mean, float $rise): array {
    $raw = [];
    for ($i = 0; $i < $days; $i++) {
        $t = $days === 1 ? 0.0 : ($i / ($days - 1) - 0.5);
        $wave = sin($i * 0.9) * 2.6 + cos($i * 0.45) * 1.5;
        $raw[] = $mean + $rise * $t + $wave;
    }
    $shift = $mean - (array_sum($raw) / max(1, $days));
    return array_map(static fn (float $value): float => round($value + $shift, 1), $raw);
};
$periods = [
    'Dnes' => [
        'average' => '62.4',
        'delta' => '+4.0',
        'deltaNote' => 'celkový průměr · oproti včerejšku',
        'kpis' => [
            ['Prvních 9', '71.2', 'průměr úvodních tří kol'],
            ['Rozhodující legy', '50 %', '1 z 2 vyhráno'],
            ['Legy', '4/6', 'vyhrané / odehrané'],
            ['180', '1', 'kol s maximem'],
        ],
        'chart' => $dailyAverages(1, 62.4, 0),
        'bands' => [
            ['180', 1, '#f24747'],
            ['140+', 2, '#ff8a3d'],
            ['100+', 4, '#f5c518'],
            ['80+', 3, '#8fd14f'],
            ['60+', 4, '#4dd984'],
            ['40+', 2, '#3ec6d4'],
            ['20+', 1, '#5b86e5'],
            ['pod 20', 1, '#8e8e93'],
        ],
        'visits' => 18,
        'time' => [
            ['Sám', 18, '#fefc03'],
            ['S botem', 24, '#4dd984'],
            ['S kamarády', 0, '#5b86e5'],
        ],
    ],
    '7 dní' => [
        'average' => '60.2',
        'delta' => '+1.8',
        'deltaNote' => 'celkový průměr · oproti předchozím 7 dnům',
        'kpis' => [
            ['Prvních 9', '67.4', 'průměr úvodních tří kol'],
            ['Rozhodující legy', '60 %', '3 z 5 vyhráno'],
            ['Legy', '18/29', 'vyhrané / odehrané'],
            ['180', '4', 'kol s maximem'],
        ],
        'chart' => $dailyAverages(7, 60.2, 8),
        'bands' => [
            ['180', 4, '#f24747'],
            ['140+', 6, '#ff8a3d'],
            ['100+', 14, '#f5c518'],
            ['80+', 16, '#8fd14f'],
            ['60+', 22, '#4dd984'],
            ['40+', 18, '#3ec6d4'],
            ['20+', 10, '#5b86e5'],
            ['pod 20', 6, '#8e8e93'],
        ],
        'visits' => 96,
        'time' => [
            ['Sám', 40, '#fefc03'],
            ['S botem', 80, '#4dd984'],
            ['S kamarády', 35, '#5b86e5'],
        ],
    ],
    '30 dní' => [
        'average' => '58.4',
        'delta' => '+2.4',
        'deltaNote' => 'celkový průměr · oproti předchozím 30 dnům',
        'kpis' => [
            ['Prvních 9', '64.1', 'průměr úvodních tří kol'],
            ['Rozhodující legy', '55 %', '6 z 11 vyhráno'],
            ['Legy', '41/72', 'vyhrané / odehrané'],
            ['180', '9', 'kol s maximem'],
        ],
        'chart' => $dailyAverages(30, 58.4, 10),
        'bands' => [
            ['180', 9, '#f24747'],
            ['140+', 18, '#ff8a3d'],
            ['100+', 46, '#f5c518'],
            ['80+', 70, '#8fd14f'],
            ['60+', 92, '#4dd984'],
            ['40+', 81, '#3ec6d4'],
            ['20+', 64, '#5b86e5'],
            ['pod 20', 40, '#8e8e93'],
        ],
        'visits' => 420,
        'time' => [
            ['Sám', 130, '#fefc03'],
            ['S botem', 275, '#4dd984'],
            ['S kamarády', 110, '#5b86e5'],
        ],
    ],
    'Celé období' => [
        'average' => '56.8',
        'delta' => '+6.1',
        'deltaNote' => 'celkový průměr od první hry',
        'kpis' => [
            ['Prvních 9', '61.0', 'průměr úvodních tří kol'],
            ['Rozhodující legy', '45 %', '14 z 31 vyhráno'],
            ['Legy', '188/340', 'vyhrané / odehrané'],
            ['180', '22', 'kol s maximem'],
        ],
        'chart' => $dailyAverages(90, 56.8, 16),
        'bands' => [
            ['180', 22, '#f24747'],
            ['140+', 80, '#ff8a3d'],
            ['100+', 210, '#f5c518'],
            ['80+', 340, '#8fd14f'],
            ['60+', 460, '#4dd984'],
            ['40+', 390, '#3ec6d4'],
            ['20+', 310, '#5b86e5'],
            ['pod 20', 188, '#8e8e93'],
        ],
        'visits' => 2000,
        'time' => [
            ['Sám', 372, '#fefc03'],
            ['S botem', 1120, '#4dd984'],
            ['S kamarády', 545, '#5b86e5'],
        ],
    ],
];
$defaultPeriod = '30 dní';

$boardShape = [
    20 => [150, 18, 40], 1 => [12, 2, 2], 18 => [18, 3, 4], 4 => [8, 1, 1],
    13 => [9, 1, 2], 6 => [10, 2, 2], 10 => [14, 2, 3], 15 => [7, 1, 1],
    2 => [9, 2, 1], 17 => [8, 1, 1], 3 => [16, 2, 4], 19 => [13, 2, 3],
    7 => [6, 1, 1], 16 => [7, 1, 1], 8 => [5, 1, 1], 11 => [4, 0, 1],
    14 => [5, 1, 1], 9 => [3, 0, 0], 12 => [0, 0, 0], 5 => [6, 1, 1],
];
$scaleBoard = static function (float $factor, array $overrides = [], array $bull = [1, 1]) use ($boardShape): array {
    $segments = [];
    foreach ($boardShape as $number => $parts) {
        $segments[$number] = $overrides[$number] ?? array_map(
            static fn (int $count): int => (int) round($count * $factor),
            $parts
        );
    }

    return ['segments' => $segments, 'bull' => $bull];
};
$boards = [
    'Dnes' => $scaleBoard(0.15, [20 => [22, 3, 6], 12 => [0, 0, 0]], [1, 1]),
    '7 dní' => $scaleBoard(0.42, [20 => [58, 7, 15], 12 => [0, 0, 0]], [4, 2]),
    '30 dní' => $scaleBoard(1, [], [8, 5]),
    'Celé období' => $scaleBoard(3.4, [
        20 => [360, 48, 80],
        1 => [70, 12, 14],
        5 => [40, 6, 6],
        12 => [10, 1, 1],
    ], [28, 16]),
];
$focus = [
    'Dnes' => [
        'misses' => 2,
        'wins' => ['rate' => 67, 'won' => 2, 'played' => 3, 'form' => [1, 0]],
        'closing' => [
            'rate' => 40, 'hits' => 2, 'attempts' => 5, 'count' => 2, 'highest' => 120, 'average' => 80,
            'best' => ['D20', 1, 1], 'worst' => ['D16', 0, 2],
            'doubles' => [['D20', 1, 1], ['D10', 1, 2], ['D16', 0, 2]],
            'ranges' => [['2–40', 1], ['41–80', 0], ['81–100', 1], ['101+', 0]],
        ],
    ],
    '7 dní' => [
        'misses' => 6,
        'wins' => ['rate' => 67, 'won' => 8, 'played' => 12, 'form' => [1, 0, 1, 1, 0]],
        'closing' => [
            'rate' => 35, 'hits' => 11, 'attempts' => 31, 'count' => 11, 'highest' => 121, 'average' => 68,
            'best' => ['D20', 6, 12], 'worst' => ['D16', 1, 7],
            'doubles' => [['D20', 6, 12], ['D18', 2, 4], ['D10', 1, 4], ['D8', 1, 4], ['D16', 1, 7]],
            'ranges' => [['2–40', 4], ['41–80', 4], ['81–100', 2], ['101+', 1]],
        ],
    ],
    '30 dní' => [
        'misses' => 18,
        'wins' => ['rate' => 64, 'won' => 18, 'played' => 28, 'form' => [1, 0, 1, 1, 0]],
        'closing' => [
            'rate' => 32, 'hits' => 18, 'attempts' => 56, 'count' => 18, 'highest' => 121, 'average' => 74,
            'best' => ['D20', 8, 16], 'worst' => ['D16', 1, 9],
            'doubles' => [['D20', 8, 16], ['D18', 3, 7], ['D10', 2, 6], ['D8', 2, 6], ['D12', 1, 6], ['D16', 1, 9], ['D5', 1, 4], ['D4', 0, 2]],
            'ranges' => [['2–40', 6], ['41–80', 7], ['81–100', 3], ['101+', 2]],
        ],
    ],
    'Celé období' => [
        'misses' => 64,
        'wins' => ['rate' => 57, 'won' => 64, 'played' => 112, 'form' => [1, 0, 1, 1, 0]],
        'closing' => [
            'rate' => 28, 'hits' => 74, 'attempts' => 264, 'count' => 74, 'highest' => 141, 'average' => 61,
            'best' => ['D20', 28, 70], 'worst' => ['D16', 4, 28],
            'doubles' => [['D20', 28, 70], ['D18', 12, 36], ['D10', 9, 28], ['D8', 8, 26], ['D12', 7, 24], ['D16', 4, 28], ['D5', 4, 22], ['D4', 2, 18]],
            'ranges' => [['2–40', 22], ['41–80', 28], ['81–100', 14], ['101+', 10]],
        ],
    ],
];
foreach ($periods as $name => &$periodRow) {
    $periodRow['board'] = $boards[$name];
    $periodRow['misses'] = $focus[$name]['misses'];
    $periodRow['wins'] = $focus[$name]['wins'];
    $periodRow['closing'] = $focus[$name]['closing'];
}
unset($periodRow);
$active = $periods[$defaultPeriod];

$dash = [
    'default' => $defaultPeriod,
    'periods' => $periods,
    'maxDays' => ['Dnes' => 0, '7 dní' => 7, '30 dní' => 30, 'Celé období' => 9999],
];

$minutes = static function (int $m): string {
    $h = intdiv($m, 60);
    $min = $m % 60;
    if ($h === 0) {
        return $min . ' min';
    }
    return $h . ' h ' . str_pad((string) $min, 2, '0', STR_PAD_LEFT) . ' min';
};
$timeTotal = array_sum(array_column($active['time'], 1));
?>
<main class="@container/dash mx-auto w-full max-w-7xl">
<header class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
<div>
<p class="eyebrow">Výkon</p>
<h1 class="mt-2">Statistiky</h1>
<p class="mt-1 max-w-xl text-sm text-zinc-400">Průměr, zavírání, skórování, terč i čas u desky. Přepni období a projdi celý výkon.</p>
</div>
<div class="flex flex-wrap items-center gap-2">
<div class="seg" role="group" aria-label="Období">
<?php foreach (array_keys($periods) as $name): ?>
<button class="<?= $name === $defaultPeriod ? 'active' : '' ?>" type="button" data-period="<?= e($name) ?>"><?= e($name) ?></button>
<?php endforeach; ?>
</div>
<a class="icon-btn" href="<?= e(url('/app/games')) ?>" aria-label="Moje hry" title="Moje hry"><?= dartiqo_i('arrow') ?></a>
</div>
</header>

<section id="stats" class="flex flex-col gap-4" aria-labelledby="stats-title">
<h2 id="stats-title" class="sr-only">Celkový výkon</h2>
<?php $closing = $active['closing']; $wins = $active['wins']; $bandTotal = array_sum(array_column($active['bands'], 1)); ?>
<div class="panel grid items-stretch gap-6 p-0! @4xl/dash:grid-cols-[minmax(0,.75fr)_minmax(0,1.25fr)]">
<span class="pointer-events-none absolute -top-24 -left-24 size-80 rounded-full bg-accent/10 blur-3xl" aria-hidden="true"></span>
<div class="relative flex flex-col justify-between gap-6 p-6 sm:p-7 @4xl/dash:border-r @4xl/dash:border-white/[.06]">
<div>
<p class="eyebrow">Průměr na 3 šipky</p>
<div class="mt-3 flex items-end gap-3">
<span id="stat-average" class="num text-7xl leading-none"><?= e($active['average']) ?></span>
<em id="stat-delta" class="chip mb-2 bg-emerald-400/10 text-emerald-300 not-italic ring-1 ring-emerald-400/25 ring-inset"><?= e($active['delta']) ?></em>
</div>
<p id="stat-delta-note" class="mt-3 text-sm text-zinc-400"><?= e($active['deltaNote']) ?></p>
</div>
<div class="grid grid-cols-3 gap-2">
<div class="tile p-3"><span class="eyebrow tracking-[.1em]">Výhry</span><strong id="hero-win" class="num mt-1 block text-xl text-emerald-300"><?= e((string) $wins['rate']) ?> %</strong></div>
<div class="tile p-3"><span class="eyebrow tracking-[.1em]">Double</span><strong id="hero-co" class="num mt-1 block text-xl text-accent"><?= e((string) $closing['rate']) ?> %</strong></div>
<div class="tile p-3"><span class="eyebrow tracking-[.1em]">Zápasy</span><strong id="hero-played" class="num mt-1 block text-xl"><?= e((string) $wins['played']) ?></strong></div>
</div>
</div>
<div class="relative p-6 pt-0 sm:p-7 sm:pt-0 @4xl/dash:pt-7!">
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
<h3>Průběh po dnech</h3>
<div class="seg p-0.5 [&>button]:h-7 [&>button]:px-3 [&>button]:text-[11px]" role="group" aria-label="Typ grafu">
<button class="active" type="button" data-trend="bar" aria-pressed="true">Sloupce</button>
<button type="button" data-trend="line" aria-pressed="false">Linka</button>
<button type="button" data-trend="area" aria-pressed="false">Plocha</button>
</div>
</div>
<div class="relative" style="height:14rem">
<canvas id="stat-chart" role="img" aria-label="Denní průměr na 3 šipky"></canvas>
</div>
</div>
</div>

<div class="grid grid-cols-2 gap-4 @3xl/dash:grid-cols-4" id="kpi-grid">
<?php foreach ($active['kpis'] as [$label, $value, $detail]): ?>
<article class="panel p-4! sm:p-5!"><span class="eyebrow tracking-[.12em]"><?= e($label) ?></span><strong class="num mt-3 block text-3xl leading-none"><?= e($value) ?></strong><small class="mt-2 block text-xs text-zinc-500"><?= e($detail) ?></small></article>
<?php endforeach; ?>
</div>

<div class="grid items-start gap-4 @4xl/dash:grid-cols-2">
<article class="panel">
<div class="flex items-start justify-between gap-4">
<div>
<p class="eyebrow">Zavírání</p>
<strong id="co-rate" class="num mt-3 block text-5xl leading-none text-accent"><?= e((string) $closing['rate']) ?> %</strong>
<p class="mt-2 text-sm text-zinc-400">úspěšnost na double</p>
</div>
<div class="tile px-4 py-3 text-right">
<strong id="co-ratio" class="num block text-xl"><?= e((string) $closing['hits']) ?> / <?= e((string) $closing['attempts']) ?></strong>
<p class="mt-0.5 text-[11px] text-zinc-500">zavření / šipky</p>
</div>
</div>
<div class="meter mt-5 h-2"><i id="co-bar" class="bg-accent shadow-[0_0_12px_#faff0080]" style="width:<?= e((string) $closing['rate']) ?>%"></i></div>
<div class="mt-5 grid gap-2 sm:grid-cols-2" id="co-extremes">
<?php foreach ([['Nejlepší double', $closing['best'], 'best'], ['Nejslabší double', $closing['worst'], 'worst']] as [$title, $row, $kind]): $pct = $row[2] ? (int) round($row[1] / $row[2] * 100) : 0; ?>
<div class="rounded-2xl p-4 ring-1 ring-inset <?= $kind === 'best' ? 'bg-emerald-400/[.07] ring-emerald-400/25' : 'bg-rose-400/[.07] ring-rose-400/25' ?>">
<span class="text-[10px] font-semibold tracking-widest uppercase <?= $kind === 'best' ? 'text-emerald-300' : 'text-rose-300' ?>"><?= e($title) ?></span>
<div class="mt-1 flex items-end justify-between gap-2"><strong class="num text-2xl"><?= e($row[0]) ?></strong><b class="num <?= $kind === 'best' ? 'text-emerald-300' : 'text-rose-300' ?>"><?= e((string) $pct) ?> %</b></div>
<small class="text-xs text-zinc-500"><?= e((string) $row[1]) ?> z <?= e((string) $row[2]) ?> pokusů</small>
</div>
<?php endforeach; ?>
</div>
</article>
<article class="panel">
<span class="pointer-events-none absolute -top-16 -right-16 size-56 rounded-full bg-emerald-400/10 blur-3xl" aria-hidden="true"></span>
<div class="relative">
<p class="eyebrow">Výhry</p>
<strong id="win-rate" class="num mt-3 block text-5xl leading-none text-emerald-300"><?= e((string) $wins['rate']) ?> %</strong>
<p id="win-ratio" class="mt-2 text-sm text-zinc-400"><?= e((string) $wins['won']) ?> z <?= e((string) $wins['played']) ?> zápasů</p>
</div>
<div class="meter relative mt-5 h-2"><i id="win-bar" class="bg-emerald-400 shadow-[0_0_12px_#34d39980]" style="width:<?= e((string) $wins['rate']) ?>%"></i></div>
<div class="relative mt-5 flex items-center justify-between gap-3">
<div>
<p class="eyebrow">Forma</p>
<p class="mt-1 text-xs text-zinc-500">výhry a prohry v období</p>
</div>
<div class="flex gap-1.5" id="win-form">
<?php foreach ($wins['form'] as $won): ?>
<i class="grid size-8 place-items-center rounded-lg text-[11px] font-bold not-italic <?= $won ? 'bg-emerald-400/90 text-black' : 'bg-white/[.06] text-zinc-500' ?>"><?= $won ? 'V' : 'P' ?></i>
<?php endforeach; ?>
</div>
</div>
<div class="relative mt-5 grid grid-cols-3 gap-2 text-center">
<div class="rounded-2xl bg-emerald-400/[.07] px-2 py-3 ring-1 ring-emerald-400/20 ring-inset"><strong id="win-won" class="num block text-2xl text-emerald-300"><?= e((string) $wins['won']) ?></strong><span class="text-[10px] tracking-widest text-zinc-500 uppercase">výhry</span></div>
<div class="tile px-2 py-3"><strong id="win-lost" class="num block text-2xl"><?= e((string) ($wins['played'] - $wins['won'])) ?></strong><span class="text-[10px] tracking-widest text-zinc-500 uppercase">prohry</span></div>
<div class="tile px-2 py-3"><strong id="win-played" class="num block text-2xl"><?= e((string) $wins['played']) ?></strong><span class="text-[10px] tracking-widest text-zinc-500 uppercase">zápasů</span></div>
</div>
</article>
</div>

<div class="grid items-start gap-4 @5xl/dash:grid-cols-2">
<article class="panel">
<div class="mb-4">
<h3>Podle doublu</h3>
<p id="co-meta" class="mt-1 text-xs text-zinc-500"><?= e((string) $closing['count']) ?> zavření · nejvyšší <?= e((string) $closing['highest']) ?> · průměrné <?= e((string) $closing['average']) ?></p>
</div>
<div class="relative mt-1" id="co-chart-box" style="height:14rem">
<canvas id="co-chart" role="img" aria-label="Úspěšnost podle doublu"></canvas>
</div>
<div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-4" id="co-ranges">
<?php foreach ($closing['ranges'] as [$label, $count]): ?>
<div class="tile px-3 py-2.5"><strong class="num block text-xl"><?= e((string) $count) ?></strong><span class="text-[10px] tracking-wide text-zinc-500 uppercase"><?= e($label) ?></span></div>
<?php endforeach; ?>
</div>
</article>
<article class="panel">
<div class="mb-4 flex items-start justify-between gap-3"><div><h3>Skórování</h3><p class="mt-1 text-xs text-zinc-500">Rozložení kol ve zvoleném období.</p></div><span id="score-total" class="chip bg-white/[.06] text-zinc-300"><?= e((string) $bandTotal) ?> kol</span></div>
<div class="relative" style="height:16rem">
<canvas id="score-chart" role="img" aria-label="Rozložení kol podle skóre"></canvas>
</div>
<div class="mt-4 grid grid-cols-4 gap-2" id="score-grid">
<?php foreach ($active['bands'] as [$label, $count, $color]): ?>
<div class="tile px-2 py-2 text-center"><strong class="num block text-lg"><?= e((string) $count) ?></strong><span class="mt-0.5 inline-flex items-center gap-1 text-[10px] text-zinc-400"><i class="size-1.5 rounded-full" style="background:<?= e($color) ?>"></i><?= e($label) ?></span></div>
<?php endforeach; ?>
</div>
</article>
</div>

<div class="grid gap-4 @4xl/dash:grid-cols-2">
<article class="panel bg-[#11151d]/80!">
<div class="mb-2 flex items-start justify-between gap-3">
<div><span class="eyebrow">Nejmíň</span><strong id="aim-min" class="num mt-1 block text-2xl text-zinc-100">0,0 %</strong></div>
<div class="text-right"><span class="eyebrow text-[#e8b03d]/80">Nejvíc</span><strong id="aim-max" class="num mt-1 block text-2xl text-[#e8b03d]">0 %</strong></div>
</div>
<div id="aim-chart"></div>
<div class="mt-3 flex items-center justify-between gap-3">
<h3>Podíl zásahů</h3>
<span class="chip bg-white/[.06] text-zinc-300">Bull <b id="aim-bull" class="text-zinc-100">0 %</b></span>
</div>
<div class="mt-3 flex flex-wrap gap-3 text-[11px] font-medium text-zinc-400 [&_i]:mr-1.5 [&_i]:inline-block [&_i]:size-2 [&_i]:rounded-full">
<span><i style="background:#fce06b"></i>Single</span>
<span><i style="background:#d6851f"></i>Double</span>
<span><i style="background:#66330d"></i>Triple</span>
</div>
<p id="aim-detail" class="mt-3 text-xs text-zinc-500" aria-live="polite">Plocha je single, tmavý pruh triple a okraj double. Klepni na výseč pro počty.</p>
</article>
<article class="panel">
<div class="mb-4 flex items-start justify-between gap-3">
<div>
<h3>Mapa dopadů</h3>
<p class="mt-1 text-xs text-zinc-500">Přesná místa na terči, jako v aplikaci.</p>
</div>
<div class="seg p-0.5">
<button class="w-8 justify-center px-0! text-base" type="button" data-heat-zoom="-0.35" aria-label="Oddálit mapu">−</button>
<button class="w-8 justify-center px-0! text-base" type="button" data-heat-zoom="0.35" aria-label="Přiblížit mapu">+</button>
</div>
</div>
<div class="rounded-3xl bg-black p-2 ring-1 ring-white/[.06] ring-inset">
<svg id="heat-board" class="mx-auto aspect-square w-full" viewBox="0 0 500 500" role="img" aria-label="Mapa dopadů šipek na terči"></svg>
</div>
<p class="mt-3 text-xs text-zinc-500">Každá tečka je jeden hod. Přibliž si segment tlačítky + a −.</p>
<div class="mt-4 flex flex-col gap-2.5" id="map-shares"></div>
<h3 class="mt-6">Nejčastější pole</h3>
<div class="mt-3 grid grid-cols-2 gap-2" id="map-fields"></div>
</article>
</div>

<article class="panel">
<div class="mb-5 flex items-center justify-between gap-3"><div><p class="eyebrow">Čas u terče</p><h3 class="mt-1">Jak hraješ</h3></div><span id="time-total" class="chip bg-white/[.06] text-zinc-300"><?= e($minutes($timeTotal)) ?></span></div>
<div class="grid items-center gap-6 sm:grid-cols-[12rem_1fr]">
<div class="relative mx-auto w-full" style="height:12rem;max-width:12rem">
<canvas id="time-chart" role="img" aria-label="Čas u terče podle způsobu hry"></canvas>
</div>
<div class="grid gap-2 text-sm text-zinc-300 sm:grid-cols-3 [&>span]:flex [&>span]:items-center [&>span]:justify-between [&>span]:gap-3 [&>span]:rounded-2xl [&>span]:bg-white/[.035] [&>span]:px-4 [&>span]:py-3 [&>span]:ring-1 [&>span]:ring-white/[.06] [&>span]:ring-inset [&_i]:mr-2 [&_i]:inline-block [&_i]:size-2.5 [&_i]:rounded-full [&_b]:font-display [&_b]:font-semibold [&_b]:text-white [&_b]:tabular-nums" id="time-legend">
<?php foreach ($active['time'] as [$label, $mins, $color]): ?>
<span><span class="flex items-center"><i style="background:<?= e($color) ?>"></i><?= e($label) ?></span><b><?= e($minutes($mins)) ?></b></span>
<?php endforeach; ?>
</div>
</div>
</article>
</section>

<section class="mt-10" aria-labelledby="records-title">
<div class="mb-5">
<p class="eyebrow">Rekordy</p>
<h2 class="mt-1" id="records-title">Co už máš za sebou</h2>
</div>
<div class="grid grid-cols-2 gap-3 @md/dash:grid-cols-3 @5xl/dash:grid-cols-6">
<?php foreach ([['Průměr zápasu', '74.2', 'target'], ['Nejlepší kolo', '180', 'bolt'], ['Nejvyšší zavření', '141', 'trophy'], ['Šipek na leg', '14', 'flag'], ['Zápasů', '112', 'chart'], ['Výher', '64', 'check']] as [$label, $value, $icon]): ?>
<article class="panel p-4!">
<span class="grid size-8 place-items-center rounded-lg bg-accent/10 text-accent ring-1 ring-accent/20 ring-inset"><?= dartiqo_i($icon, 'size-4') ?></span>
<strong class="num mt-4 block text-3xl leading-none"><?= e($value) ?></strong>
<span class="mt-1.5 block text-xs text-zinc-500"><?= e($label) ?></span>
</article>
<?php endforeach; ?>
</div>
<?php
$miles = [
    ['První zápas', true],
    ['První výhra', true],
    ['Maximum 180', true],
    ['Zavření 100+', true],
    ['20 zápasů', true],
    ['50 výher', true],
    ['7 dní v řadě', false],
    ['Zavření 170', false],
];
$milesDone = count(array_filter($miles, static fn (array $m): bool => $m[1]));
?>
<div class="panel mt-4">
<div class="mb-4 flex items-center justify-between gap-3"><h3>Milníky</h3><span class="text-xs text-zinc-500"><b class="text-zinc-200"><?= e((string) $milesDone) ?></b> / <?= e((string) count($miles)) ?></span></div>
<div class="meter mb-5"><i class="bg-accent" style="width:<?= e((string) round($milesDone / count($miles) * 100)) ?>%"></i></div>
<div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
<?php foreach ($miles as [$label, $done]): ?>
<div class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-xs <?= $done ? 'bg-accent/[.06] text-zinc-100 ring-1 ring-accent/25 ring-inset' : 'tile text-zinc-500' ?>"><b class="grid size-6 shrink-0 place-items-center rounded-full <?= $done ? 'bg-accent text-black' : 'bg-white/10' ?>"><?= $done ? dartiqo_i('check', 'size-3.5') : '–' ?></b><span><?= e($label) ?></span></div>
<?php endforeach; ?>
</div>
</div>
<p class="mt-6 text-xs text-zinc-600">Náhled statistik. Čísla jsou ukázková. Jednotlivé zápasy jsou v Moje hry.</p>
</section>
</main>

<script src="<?= e(asset('js/vendor/chart.umd.min.js')) ?>"></script>
<script type="application/json" id="dash-data"><?= json_encode($dash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
