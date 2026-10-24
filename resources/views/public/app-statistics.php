<?php
require_once __DIR__ . '/../partials/ui.php';

/** @var \App\Services\MatchInsights $insights */
if ($insights->isEmpty()) {
    echo dartiqo_empty_page('Statistiky', 'Tvoje čísla', 'Zatím žádné statistiky', 'Průměr, zavírání a mapa zásahů se tu ukážou po prvním dohraném zápase z aplikace.', 'chart');
    return;
}
$dash = $insights->statistics();
$periods = $dash['periods'];
$defaultPeriod = $dash['default'];
$active = $periods[$defaultPeriod];
$records = $insights->records();
$miles = $insights->milestones();

$minutes = static function (int $m): string {
    $h = intdiv($m, 60);
    $min = $m % 60;
    if ($h === 0) {
        return $min . ' min';
    }
    return $h . ' h ' . str_pad((string) $min, 2, '0', STR_PAD_LEFT) . ' min';
};
$timeTotal = array_sum(array_column($active['time'], 1));
$timeDaily = (int) round($timeTotal / max(1, count($active['chart'])));
$timeTop = array_reduce($active['time'], static fn (?array $top, array $row): array => $top === null || $row[1] > $top[1] ? $row : $top);
$timeShare = static fn (int $m): int => $timeTotal ? (int) round($m / $timeTotal * 100) : 0;
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

<div class="grid gap-4 @5xl/dash:grid-cols-2">
<article class="panel flex flex-col">
<div class="mb-4 flex items-start justify-between gap-3"><div><h3>Podle doublu</h3><p id="co-meta" class="mt-1 text-xs text-zinc-500"><?= e((string) $closing['count']) ?> zavření · nejvyšší <?= e((string) $closing['highest']) ?> · Ø <?= e((string) $closing['average']) ?></p></div><span id="co-total" class="chip bg-white/[.06] text-zinc-300"><?= e((string) $closing['attempts']) ?> pokusů</span></div>
<div class="flex flex-1 flex-col gap-1.5" id="co-list">
<?php foreach ($closing['doubles'] as [$name, $hits, $attempts]):
    $pct = $attempts ? (int) round($hits / $attempts * 100) : 0;
    [$fill, $ink] = $name === $closing['best'][0] ? ['#4ade80', 'text-emerald-300'] : ($name === $closing['worst'][0] ? ['#fb7185', 'text-rose-300'] : ['#faff00', 'text-zinc-100']);
?>
<div class="tile flex items-center gap-3 px-3 py-2"><strong class="num w-9 shrink-0 text-sm"><?= e($name) ?></strong><div class="meter flex-1"><i style="width:<?= e((string) $pct) ?>%;background:<?= e($fill) ?>"></i></div><small class="num w-11 shrink-0 text-right text-[11px] text-zinc-500"><?= e((string) $hits) ?>/<?= e((string) $attempts) ?></small><b class="num w-12 shrink-0 text-right text-sm whitespace-nowrap <?= e($ink) ?>"><?= e((string) $pct) ?> %</b></div>
<?php endforeach; ?>
</div>
<p class="mt-5 mb-2 text-[10px] font-semibold tracking-widest text-zinc-500 uppercase">Zavření podle zbytku</p>
<div class="grid grid-cols-4 gap-2" id="co-ranges">
<?php foreach ($closing['ranges'] as [$label, $count]): ?>
<div class="tile px-2 py-2 text-center"><strong class="num block text-lg"><?= e((string) $count) ?></strong><span class="mt-0.5 block text-[10px] text-zinc-400"><?= e($label) ?></span></div>
<?php endforeach; ?>
</div>
</article>
<article class="panel flex flex-col">
<div class="mb-4 flex items-start justify-between gap-3"><div><h3>Skórování</h3><p class="mt-1 text-xs text-zinc-500">Rozložení kol ve zvoleném období.</p></div><span id="score-total" class="chip bg-white/[.06] text-zinc-300"><?= e((string) $bandTotal) ?> kol</span></div>
<div class="relative min-h-64 flex-1">
<div class="absolute inset-0"><canvas id="score-chart" role="img" aria-label="Rozložení kol podle skóre"></canvas></div>
</div>
<div class="mt-4 grid grid-cols-4 gap-2" id="score-grid">
<?php foreach ($active['bands'] as [$label, $count, $color]): ?>
<div class="tile px-2 py-2 text-center"><strong class="num block text-lg"><?= e((string) $count) ?></strong><span class="mt-0.5 inline-flex items-center gap-1 text-[10px] text-zinc-400"><i class="size-1.5 rounded-full" style="background:<?= e($color) ?>"></i><?= e($label) ?></span></div>
<?php endforeach; ?>
</div>
</article>
</div>

<div class="grid gap-4 @4xl/dash:grid-cols-2">
<article class="panel">
<div class="mb-4 flex items-start justify-between gap-3">
<div><p class="eyebrow">Heatmapa</p><h3 class="mt-1">Podíl zásahů</h3><p class="mt-1 text-xs text-zinc-500">Čím světlejší pole, tím častěji ho trefuješ.</p></div>
</div>
<div id="aim-chart"></div>
<div class="mt-4 flex items-center gap-3 text-[11px] text-zinc-500"><span>Méně</span><span class="h-1.5 flex-1 rounded-full bg-[linear-gradient(90deg,#221e1e,#5c2616,#d65c12,#fac800,#faff00,#feffd6)]"></span><span>Více</span></div>
<div class="mt-4 grid grid-cols-3 gap-2">
<div class="tile p-3"><span class="eyebrow tracking-[.1em]">Nejméně</span><strong id="aim-min" class="num mt-1 block text-xl">0,0 %</strong></div>
<div class="tile p-3"><span class="eyebrow tracking-[.1em]">Nejvíce</span><strong id="aim-max" class="num mt-1 block text-xl text-accent">0 %</strong></div>
<div class="tile p-3"><span class="eyebrow tracking-[.1em]">Bull</span><strong id="aim-bull" class="num mt-1 block text-xl">0 %</strong></div>
</div>
<div id="aim-detail" class="tile mt-2 p-4 text-xs text-zinc-500" aria-live="polite">Klepni na číslo na terči pro počty.</div>
<h3 class="mt-6">Rozložení zásahů</h3>
<div class="mt-3 flex flex-col gap-2.5" id="map-shares"></div>
</article>
<article class="panel">
<div class="mb-4 flex items-start justify-between gap-3">
<div><p class="eyebrow">Mapa dopadů</p><h3 class="mt-1">Kam šipky padají</h3><p class="mt-1 text-xs text-zinc-500">Tečky jsou hody. Žlutě je číslo, kam míříš nejčastěji, a vedle něj vidíš, kolik šipek uletí do stran.</p></div>
<div class="seg shrink-0 p-0.5">
<button class="w-8 justify-center px-0! text-base" type="button" data-heat-zoom="-0.35" aria-label="Oddálit mapu">−</button>
<button class="w-8 justify-center px-0! text-base" type="button" data-heat-zoom="0.35" aria-label="Přiblížit mapu">+</button>
</div>
</div>
<div class="overflow-hidden rounded-3xl bg-[radial-gradient(circle_at_50%_45%,#18181c,#09090b_70%)] p-4 ring-1 ring-white/[.06] ring-inset">
<svg id="heat-board" class="mx-auto aspect-square w-full" viewBox="0 0 500 500" role="img" aria-label="Mapa dopadů šipek na terči"></svg>
</div>
<div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-[11px] text-zinc-400">
<span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-zinc-50 ring-2 ring-black"></i>Hod</span>
<span class="inline-flex items-center gap-1.5"><i class="h-1.5 w-6 rounded-full bg-[linear-gradient(90deg,#ff4800,#ffc400,#faff00,#ffffe6)]"></i>Hustota</span>
<span class="inline-flex items-center gap-1.5"><i class="size-3 rounded-[3px] border-2 border-accent"></i>Hlavní cíl</span>
<span class="inline-flex items-center gap-1.5"><i class="size-3 rounded-[3px] border border-dashed border-zinc-300"></i>Sousední čísla</span>
</div>
<div class="mt-4 flex flex-col gap-2" id="heat-stats"></div>
<h3 class="mt-6">Nejčastější pole</h3>
<div class="mt-3 grid gap-x-6 gap-y-2.5 sm:grid-flow-col sm:grid-cols-2 sm:grid-rows-3" id="map-fields"></div>
</article>
</div>

<article class="panel">
<span class="pointer-events-none absolute -right-20 -bottom-24 size-72 rounded-full bg-accent/[.06] blur-3xl" aria-hidden="true"></span>
<div class="relative mb-6 flex flex-wrap items-start justify-between gap-3">
<div><p class="eyebrow">Čas u terče</p><h3 class="mt-1">Jak hraješ</h3><p class="mt-1 text-xs text-zinc-500">Kolik času trávíš sám, s botem a s kamarády.</p></div>
<span class="chip bg-white/[.06] text-zinc-300">Ø <b id="time-daily" class="text-zinc-100"><?= e($minutes($timeDaily)) ?></b> denně</span>
</div>
<div class="relative grid items-center gap-6 @3xl/dash:grid-cols-[13rem_1fr] @3xl/dash:gap-10">
<div class="relative mx-auto size-52">
<canvas id="time-chart" role="img" aria-label="Čas u terče podle způsobu hry"></canvas>
<div class="pointer-events-none absolute inset-0 grid place-content-center text-center">
<strong id="time-total" class="num text-xl leading-none"><?= e($minutes($timeTotal)) ?></strong>
<span class="mt-1.5 text-[10px] tracking-widest text-zinc-500 uppercase">celkem</span>
</div>
</div>
<div class="flex flex-col gap-2">
<div class="flex flex-col gap-2" id="time-legend">
<?php foreach ($active['time'] as [$label, $mins, $color]): $share = $timeShare($mins); ?>
<div class="tile px-4 py-3">
<div class="flex items-center justify-between gap-3 text-sm"><span class="flex items-center gap-2.5 text-zinc-300"><i class="size-2.5 rounded-full" style="background:<?= e($color) ?>"></i><?= e($label) ?></span><span class="flex items-baseline gap-3"><b class="num text-white"><?= e($minutes($mins)) ?></b><small class="num w-9 text-right text-xs text-zinc-500"><?= e((string) $share) ?> %</small></span></div>
<div class="meter mt-2.5"><i style="width:<?= e((string) $share) ?>%;background:<?= e($color) ?>"></i></div>
</div>
<?php endforeach; ?>
</div>
<p id="time-note" class="px-1 pt-1 text-xs text-zinc-500">Nejvíc času trávíš <b class="text-zinc-200"><?= e(mb_strtolower($timeTop[0])) ?></b> – <?= e((string) $timeShare($timeTop[1])) ?> % z celku.</p>
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
<?php foreach ($records as [$label, $value, $icon]): ?>
<article class="panel p-4!">
<span class="grid size-8 place-items-center rounded-lg bg-accent/10 text-accent ring-1 ring-accent/20 ring-inset"><?= dartiqo_i($icon, 'size-4') ?></span>
<strong class="num mt-4 block text-3xl leading-none"><?= e($value) ?></strong>
<span class="mt-1.5 block text-xs text-zinc-500"><?= e($label) ?></span>
</article>
<?php endforeach; ?>
</div>
<?php
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
<p class="mt-6 text-xs text-zinc-600">Statistiky z dohraných zápasů z aplikace. Jednotlivé zápasy jsou v Moje hry.</p>
</section>
</main>

<script src="<?= e(asset('js/vendor/chart.umd.min.js')) ?>"></script>
<script type="application/json" id="dash-data"><?= json_encode($dash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
