<?php
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
<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Výkon</p>
<h1>Statistiky</h1>
<p class="mt-2 max-w-xl text-sm">Průměr, zavírání, skórování, terč i čas u desky. Přepni období a projdi celý výkon.</p>
</div>
<a class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-zinc-100" href="<?= e(url('/app/games')) ?>">Moje hry</a>
</header>

<section id="stats" aria-labelledby="stats-title">
<div class="mb-5 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Období</p>
<h2 id="stats-title">Celkový výkon</h2>
</div>
<div class="flex flex-wrap gap-2" role="group" aria-label="Období">
<?php foreach (array_keys($periods) as $name): ?>
<button class="rounded-full border border-line bg-transparent px-4 py-2 text-xs text-muted [&.active]:border-accent [&.active]:bg-accent [&.active]:text-zinc-900 controls:px-3 controls:py-[7px] controls:text-[10px]<?= $name === $defaultPeriod ? ' active' : '' ?>" type="button" data-period="<?= e($name) ?>"><?= e($name) ?></button>
<?php endforeach; ?>
</div>
</div>

<?php $closing = $active['closing']; $wins = $active['wins']; $bandTotal = array_sum(array_column($active['bands'], 1)); ?>
<div class="grid items-center gap-6 rounded-3xl border border-white/10 bg-zinc-900 p-6 lg:grid-cols-[.9fr_1.1fr]">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Průměr na 3 šipky</p>
<div class="mt-2 flex items-end gap-3 [&_span]:font-display [&_span]:text-6xl [&_span]:leading-none [&_span]:font-bold [&_em]:mb-2 [&_em]:text-sm [&_em]:font-bold [&_em]:text-accent [&_em]:not-italic"><span id="stat-average"><?= e($active['average']) ?></span><em id="stat-delta"><?= e($active['delta']) ?></em></div>
<p id="stat-delta-note"><?= e($active['deltaNote']) ?></p>
</div>
<div>
<div class="mb-1 flex flex-wrap items-center justify-between gap-2">
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Průběh po dnech</p>
<div class="flex gap-1" role="group" aria-label="Typ grafu">
<button class="rounded-full border border-line bg-transparent px-2.5 py-1 text-[10px] text-muted [&.active]:border-accent [&.active]:bg-accent [&.active]:text-zinc-900 active" type="button" data-trend="bar" aria-pressed="true">Sloupce</button>
<button class="rounded-full border border-line bg-transparent px-2.5 py-1 text-[10px] text-muted [&.active]:border-accent [&.active]:bg-accent [&.active]:text-zinc-900" type="button" data-trend="line" aria-pressed="false">Linka</button>
<button class="rounded-full border border-line bg-transparent px-2.5 py-1 text-[10px] text-muted [&.active]:border-accent [&.active]:bg-accent [&.active]:text-zinc-900" type="button" data-trend="area" aria-pressed="false">Plocha</button>
</div>
</div>
<div class="relative" style="height:13rem">
<canvas id="stat-chart" role="img" aria-label="Denní průměr na 3 šipky"></canvas>
</div>
</div>
</div>

<div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
<article class="rounded-3xl border border-white/10 bg-zinc-900 p-6">
<div class="flex items-end justify-between gap-4">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Zavírání</p>
<strong id="co-rate" class="mt-2 block font-display text-5xl leading-none text-accent"><?= e((string) $closing['rate']) ?> %</strong>
<p class="mt-2 text-sm">úspěšnost na double</p>
</div>
<div class="text-right">
<strong id="co-ratio" class="block font-display text-2xl"><?= e((string) $closing['hits']) ?> / <?= e((string) $closing['attempts']) ?></strong>
<p class="mt-1 text-xs">zavření / šipky na double</p>
</div>
</div>
<div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10"><i id="co-bar" class="block h-full rounded-full bg-accent" style="width:<?= e((string) $closing['rate']) ?>%"></i></div>
<div class="mt-4 grid gap-2 sm:grid-cols-2" id="co-extremes">
<?php foreach ([['Nejlepší double', $closing['best'], 'best'], ['Nejslabší double', $closing['worst'], 'worst']] as [$title, $row, $kind]): $pct = $row[2] ? (int) round($row[1] / $row[2] * 100) : 0; ?>
<div class="rounded-2xl border p-3 <?= $kind === 'best' ? 'border-emerald-400/35 bg-emerald-400/10' : 'border-rose-400/35 bg-rose-400/10' ?>">
<span class="text-[10px] font-bold uppercase tracking-widest <?= $kind === 'best' ? 'text-emerald-300' : 'text-rose-300' ?>"><?= e($title) ?></span>
<div class="mt-1 flex items-end justify-between gap-2"><strong class="font-display text-2xl"><?= e($row[0]) ?></strong><b class="<?= $kind === 'best' ? 'text-emerald-300' : 'text-rose-300' ?>"><?= e((string) $pct) ?> %</b></div>
<small class="text-xs text-zinc-400"><?= e((string) $row[1]) ?> z <?= e((string) $row[2]) ?> pokusů</small>
</div>
<?php endforeach; ?>
</div>
</article>
<article class="rounded-3xl border border-white/10 bg-zinc-900 p-6">
<div class="flex items-end justify-between gap-4">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Výhry</p>
<strong id="win-rate" class="mt-2 block font-display text-5xl leading-none text-emerald-400"><?= e((string) $wins['rate']) ?> %</strong>
<p id="win-ratio" class="mt-2 text-sm"><?= e((string) $wins['won']) ?> z <?= e((string) $wins['played']) ?> zápasů</p>
</div>
</div>
<div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10"><i id="win-bar" class="block h-full rounded-full bg-emerald-400" style="width:<?= e((string) $wins['rate']) ?>%"></i></div>
<div class="mt-5 flex items-center justify-between gap-3">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Forma</p>
<p class="mt-1 text-xs">výhry a prohry v tomto období</p>
</div>
<div class="flex gap-1.5" id="win-form">
<?php foreach ($wins['form'] as $won): ?>
<i class="grid size-7 place-items-center rounded-full text-[10px] font-bold <?= $won ? 'bg-emerald-400 text-black' : 'bg-white/10 text-zinc-500' ?>"><?= $won ? 'V' : 'P' ?></i>
<?php endforeach; ?>
</div>
</div>
<div class="mt-6 grid grid-cols-3 gap-2 text-center">
<div class="rounded-2xl bg-emerald-400/10 px-2 py-3"><strong id="win-won" class="block font-display text-2xl text-emerald-300"><?= e((string) $wins['won']) ?></strong><span class="text-[10px] uppercase tracking-widest text-zinc-500">výhry</span></div>
<div class="rounded-2xl bg-white/5 px-2 py-3"><strong id="win-lost" class="block font-display text-2xl"><?= e((string) ($wins['played'] - $wins['won'])) ?></strong><span class="text-[10px] uppercase tracking-widest text-zinc-500">prohry</span></div>
<div class="rounded-2xl bg-white/5 px-2 py-3"><strong id="win-played" class="block font-display text-2xl"><?= e((string) $wins['played']) ?></strong><span class="text-[10px] uppercase tracking-widest text-zinc-500">zápasů</span></div>
</div>
</article>
</div>

<article class="mt-4 rounded-3xl border border-white/10 bg-zinc-900 p-6">
<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
<div>
<h3>Podle doublu</h3>
<p id="co-meta" class="mt-1 text-xs"><?= e((string) $closing['count']) ?> zavření · nejvyšší <?= e((string) $closing['highest']) ?> · průměrné <?= e((string) $closing['average']) ?></p>
</div>
</div>
<div class="relative mt-1" id="co-chart-box" style="height:14rem">
<canvas id="co-chart" role="img" aria-label="Úspěšnost podle doublu"></canvas>
</div>
<div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-4" id="co-ranges">
<?php foreach ($closing['ranges'] as [$label, $count]): ?>
<div class="rounded-xl bg-white/5 px-3 py-2"><strong class="block font-display text-xl"><?= e((string) $count) ?></strong><span class="text-[10px] uppercase tracking-wide text-zinc-500"><?= e($label) ?></span></div>
<?php endforeach; ?>
</div>
</article>

<div class="mt-4 grid grid-cols-2 gap-3 @3xl/dash:grid-cols-4" id="kpi-grid">
<?php foreach ($active['kpis'] as [$label, $value, $detail]): ?>
<article class="rounded-2xl border border-white/10 bg-zinc-900 p-4 [&_span]:text-[10px] [&_span]:uppercase [&_span]:tracking-widest [&_span]:text-zinc-400 [&_strong]:mt-2 [&_strong]:block [&_strong]:font-display [&_strong]:text-2xl [&_small]:text-xs [&_small]:text-zinc-500"><span><?= e($label) ?></span><strong><?= e($value) ?></strong><small><?= e($detail) ?></small></article>
<?php endforeach; ?>
</div>

<article class="mt-4 rounded-3xl border border-white/10 bg-zinc-900 p-6">
<div class="mb-4 flex items-end justify-between gap-3"><div><h3>Skórování</h3><p class="mt-1 text-xs">Rozložení kol v zvoleném období.</p></div><span id="score-total" class="text-xs text-zinc-400"><?= e((string) $bandTotal) ?> kol</span></div>
<div class="relative" style="height:16rem">
<canvas id="score-chart" role="img" aria-label="Rozložení kol podle skóre"></canvas>
</div>
<div class="mt-4 grid grid-cols-4 gap-2 sm:grid-cols-8" id="score-grid">
<?php foreach ($active['bands'] as [$label, $count, $color]): ?>
<div class="rounded-xl bg-white/5 px-2 py-2 text-center"><strong class="block font-display text-lg"><?= e((string) $count) ?></strong><span class="mt-1 inline-flex items-center gap-1 text-[10px] text-zinc-400"><i class="size-1.5 rounded-full" style="background:<?= e($color) ?>"></i><?= e($label) ?></span></div>
<?php endforeach; ?>
</div>
</article>

<div class="mt-4 grid gap-4 @3xl/dash:grid-cols-2">
<article class="rounded-3xl border border-white/10 bg-[#121722] p-6 md:p-8">
<div class="mb-2 flex items-start justify-between gap-3">
<div><span class="block text-[10px] font-bold uppercase tracking-[.22em] text-zinc-500">Nejmíň</span><strong id="aim-min" class="font-display text-2xl text-zinc-100">0,0 %</strong></div>
<div class="text-right"><span class="block text-[10px] font-bold uppercase tracking-[.22em] text-[#e8b03d]/80">Nejvíc</span><strong id="aim-max" class="font-display text-2xl text-[#e8b03d]">0 %</strong></div>
</div>
<div id="aim-chart"></div>
<div class="mt-3 flex items-center justify-between gap-3">
<h3>Podíl zásahů</h3>
<span class="text-xs font-semibold text-zinc-300">Bull <b id="aim-bull" class="text-zinc-100">0 %</b></span>
</div>
<div class="mt-3 flex flex-wrap gap-3 text-[11px] font-semibold text-zinc-400 [&_i]:mr-1.5 [&_i]:inline-block [&_i]:size-2 [&_i]:rounded-full">
<span><i style="background:#fce06b"></i>Single</span>
<span><i style="background:#d6851f"></i>Double</span>
<span><i style="background:#66330d"></i>Triple</span>
</div>
<p id="aim-detail" class="mt-3 text-xs text-zinc-400" aria-live="polite">Plocha je single, tmavý pruh triple a okraj double. Klepni na výseč pro počty.</p>
</article>
<article class="rounded-3xl border border-white/10 bg-zinc-900 p-6 md:p-8">
<div class="mb-2 flex items-start justify-between gap-3">
<div>
<h3>Mapa dopadů</h3>
<p class="mt-1 text-xs">Přesná místa na terči, jako v aplikaci.</p>
</div>
<div class="flex gap-2">
<button class="flex size-9 items-center justify-center rounded-full border border-white/10 bg-transparent text-lg text-zinc-100" type="button" data-heat-zoom="-0.35" aria-label="Oddálit mapu">−</button>
<button class="flex size-9 items-center justify-center rounded-full border border-white/10 bg-transparent text-lg text-zinc-100" type="button" data-heat-zoom="0.35" aria-label="Přiblížit mapu">+</button>
</div>
</div>
<div class="rounded-3xl bg-black p-2">
<svg id="heat-board" class="mx-auto aspect-square w-full" viewBox="0 0 500 500" role="img" aria-label="Mapa dopadů šipek na terči"></svg>
</div>
<p class="mt-3 text-xs">Každá tečka je jeden hod. Přibliž si segment tlačítky + a −.</p>
<div class="mt-4 flex flex-col gap-2.5" id="map-shares"></div>
<h3 class="mt-5">Nejčastější pole</h3>
<div class="mt-3 grid grid-cols-2 gap-2" id="map-fields"></div>
</article>
</div>

<article class="rounded-3xl border border-line bg-surface p-6 md:p-8 border-white/10! bg-zinc-900! text-zinc-100! mt-4">
<div class="mb-4 flex items-center justify-between gap-3 [&_span]:text-xs [&_span]:text-zinc-400"><h3>Čas u terče</h3><span id="time-total"><?= e($minutes($timeTotal)) ?></span></div>
<div class="grid items-center gap-5 sm:grid-cols-[11rem_1fr]">
<div class="relative mx-auto w-full" style="height:11rem;max-width:11rem">
<canvas id="time-chart" role="img" aria-label="Čas u terče podle způsobu hry"></canvas>
</div>
<div class="flex flex-col gap-3 text-xs text-zinc-300 [&_i]:mr-1.5 [&_i]:inline-block [&_i]:size-2 [&_i]:rounded-full [&_b]:ml-2 [&_b]:text-white" id="time-legend">
<?php foreach ($active['time'] as [$label, $mins, $color]): ?>
<span><i style="background:<?= e($color) ?>"></i><?= e($label) ?><b><?= e($minutes($mins)) ?></b></span>
<?php endforeach; ?>
</div>
</div>
</article>
</section>

<section class="mt-10">
<div class="mb-5 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Rekordy</p>
<h2>Co už máš za sebou</h2>
</div>
</div>
<div class="grid grid-cols-2 gap-3 @md/dash:grid-cols-3 @5xl/dash:grid-cols-6 [&_article]:rounded-2xl [&_article]:border [&_article]:border-white/10 [&_article]:bg-zinc-900 [&_article]:p-4 [&_span]:text-[10px] [&_span]:uppercase [&_span]:tracking-widest [&_span]:text-zinc-500 [&_strong]:mt-1 [&_strong]:block [&_strong]:font-display [&_strong]:text-2xl">
<article><span>Průměr zápasu</span><strong>74.2</strong></article>
<article><span>Nejlepší kolo</span><strong>180</strong></article>
<article><span>Nejvyšší zavření</span><strong>141</strong></article>
<article><span>Šipek na leg</span><strong>14</strong></article>
<article><span>Zápasů</span><strong>112</strong></article>
<article><span>Výher</span><strong>64</strong></article>
</div>
<div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 [&_div]:flex [&_div]:items-center [&_div]:gap-2 [&_div]:rounded-xl [&_div]:border [&_div]:border-white/10 [&_div]:px-3 [&_div]:py-2 [&_div]:text-xs [&_div]:text-zinc-500 [&_.done]:border-accent/40 [&_.done]:text-zinc-100 [&_b]:grid [&_b]:size-6 [&_b]:place-items-center [&_b]:rounded-full [&_b]:bg-white/10 [&_.done_b]:bg-accent [&_.done_b]:text-black">
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
foreach ($miles as [$label, $done]):
?>
<div class="<?= $done ? 'done' : '' ?>"><b><?= $done ? '✓' : '–' ?></b><span><?= e($label) ?></span></div>
<?php endforeach; ?>
</div>
<p class="mt-6 text-xs text-zinc-500">Náhled statistik. Čísla jsou ukázková. Jednotlivé zápasy jsou v Moje hry.</p>
</section>
</main>

<script src="<?= e(asset('js/vendor/chart.umd.min.js')) ?>"></script>
<script type="application/json" id="dash-data"><?= json_encode($dash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
