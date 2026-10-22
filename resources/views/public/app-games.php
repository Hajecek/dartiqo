<?php
require __DIR__ . '/../partials/player-games.php';
require_once __DIR__ . '/../partials/ui.php';

$matchId = (string) ($matchId ?? '');
$games = dartiqo_games();
$game = $matchId !== '' ? dartiqo_game($matchId) : null;
$filters = [
    ['all', 'Vše', ''],
    ['win', 'Výhry', 'bg-emerald-400'],
    ['draw', 'Remízy', 'bg-amber-300'],
    ['loss', 'Prohry', 'bg-rose-400'],
    ['train', 'Trénink', 'bg-accent'],
];
?>
<main class="@container/dash mx-auto w-full max-w-7xl">
<?php if ($matchId !== '' && $game === null): ?>
<div class="panel mx-auto mt-10 max-w-lg py-12! text-center">
<span class="mx-auto grid size-14 place-items-center rounded-2xl bg-white/[.05] text-zinc-400 ring-1 ring-white/10 ring-inset"><?= dartiqo_i('search', 'size-6') ?></span>
<p class="eyebrow mt-6">Moje hry</p>
<h1 class="mt-2">Zápas nenalezen</h1>
<p class="mt-2 text-sm text-zinc-400">Tenhle odkaz nevede na žádnou ukázkovou hru.</p>
<a class="btn-ghost mt-6" href="<?= e(url('/app/games')) ?>"><?= dartiqo_i('back') ?>Zpět na hry</a>
</div>
<?php elseif ($game !== null): ?>
<?php
$style = dartiqo_result((string) $game['result']);
$versus = preg_match('/^(\S+)\s+:\s+(\S+)$/u', (string) $game['score'], $scoreParts) === 1;
$youScore = $versus ? $scoreParts[1] : (string) $game['score'];
$oppScore = $versus ? $scoreParts[2] : '';
$oppScoreClass = $game['result'] === 'draw' ? $style['text'] : ($game['result'] === 'loss' ? 'text-emerald-300' : 'text-zinc-400');
$youName = 'Alex Novák';
$isTrain = $game['kind'] === 'train';
$youCaption = $isTrain ? 'Dril' : 'Ty';
$youTitle = $isTrain ? (string) $game['title'] : $youName;
$oppCaption = $isTrain ? 'Výsledek' : 'Soupeř';
$oppTitle = $isTrain ? (string) $game['label'] : (string) $game['title'];
$oppStat = $game['oppAvg'] !== '' ? (string) $game['oppAvg'] : (string) $game['avg'];
$youStatLabel = (string) ($game['kpis'][0][0] ?? 'Průměr');
$oppStatLabel = (string) ($game['kpis'][1][0] ?? 'Průměr');
if (in_array(mb_strtolower($oppStatLabel), ['soupeř', 'souper'], true)) {
    $oppStatLabel = $youStatLabel;
}
$oppGlow = match ($game['result']) {
    'loss' => 'bg-emerald-400',
    'draw' => 'bg-amber-300',
    default => '',
};
$heroKpis = array_slice($game['kpis'], $versus ? 2 : 0, 4);
$moreKpis = array_slice($game['kpis'], ($versus ? 2 : 0) + count($heroKpis));
$stepCount = count($game['steps']);
$stepWord = $game['kind'] === 'train'
    ? ($stepCount === 1 ? 'úsek' : ($stepCount < 5 ? 'úseky' : 'úseků'))
    : ($stepCount === 1 ? 'leg' : ($stepCount < 5 ? 'legy' : 'legů'));
$viz = [
    'id' => $game['id'],
    'board' => $game['board'],
    'misses' => $game['misses'],
    'bands' => $game['bands'],
    'unit' => $game['unit'],
];
$verdict = match ($game['result']) {
    'win' => 'Výhra',
    'loss' => 'Prohra',
    'draw' => 'Remíza',
    default => 'Splněno',
};
?>
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
<a class="pill" href="<?= e(url('/app/games')) ?>"><?= dartiqo_i('back') ?>Všechny hry</a>
<div class="flex flex-wrap items-center gap-2">
<span class="<?= e(dartiqo_game_tone((string) $game['tone'])) ?>"><?= e($game['mode']) ?></span>
<span class="chip <?= e($style['soft']) ?>"><?= e($verdict) ?></span>
</div>
</div>

<section class="panel p-0!" aria-labelledby="match-title">
<h1 id="match-title" class="sr-only"><?= e($game['title']) ?>, <?= e($verdict) ?> <?= e($game['score']) ?></h1>
<?php if ($versus): ?>
<div class="relative">
<div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
<div class="absolute top-1/2 left-[30%] size-72 -translate-x-1/2 -translate-y-1/2 rounded-full opacity-20 blur-3xl <?= e($style['bar']) ?>"></div>
<?php if ($oppGlow !== ''): ?><div class="absolute top-1/2 left-[70%] size-72 -translate-x-1/2 -translate-y-1/2 rounded-full opacity-20 blur-3xl <?= e($oppGlow) ?>"></div><?php endif; ?>
</div>
<div class="relative grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center px-4 py-8 sm:px-10 sm:py-12">
<div class="min-w-0 pr-3 text-right sm:pr-8">
<span class="eyebrow"><?= e($youCaption) ?></span>
<strong class="mt-1 block truncate text-base font-semibold sm:text-xl" data-player-full><?= e($youTitle) ?></strong>
<div class="num mt-4 text-7xl leading-none sm:text-9xl <?= e($style['text']) ?>"><?= e($youScore) ?></div>
<div class="mt-4 inline-flex items-baseline gap-2 rounded-full bg-white/[.05] px-3 py-1.5 ring-1 ring-white/[.06] ring-inset">
<span class="text-[10px] font-semibold tracking-[.14em] text-zinc-500 uppercase"><?= e($youStatLabel) ?></span>
<span class="num text-base text-zinc-50"><?= e($game['avg']) ?></span>
</div>
</div>
<div class="num text-4xl leading-none text-zinc-700 sm:text-6xl" aria-hidden="true">:</div>
<div class="min-w-0 pl-3 sm:pl-8">
<span class="eyebrow"><?= e($oppCaption) ?></span>
<strong class="mt-1 block truncate text-base font-semibold sm:text-xl"><?= e($oppTitle) ?></strong>
<div class="num mt-4 text-7xl leading-none sm:text-9xl <?= e($oppScoreClass) ?>"><?= e($oppScore) ?></div>
<div class="mt-4 inline-flex items-baseline gap-2 rounded-full bg-white/[.05] px-3 py-1.5 ring-1 ring-white/[.06] ring-inset">
<span class="num text-base text-zinc-50"><?= e($oppStat) ?></span>
<span class="text-[10px] font-semibold tracking-[.14em] text-zinc-500 uppercase"><?= e($oppStatLabel) ?></span>
</div>
</div>
</div>
</div>
<?php else: ?>
<div class="relative grid @3xl/dash:grid-cols-[1.3fr_.7fr]">
<div class="pointer-events-none absolute -top-20 -left-20 size-72 rounded-full opacity-20 blur-3xl <?= e($style['bar']) ?>" aria-hidden="true"></div>
<div class="relative px-5 py-7 sm:px-10 sm:py-10">
<span class="eyebrow"><?= e($youCaption) ?></span>
<strong class="mt-3 block text-2xl font-semibold tracking-tight sm:text-4xl"><?= e($youTitle) ?></strong>
<p class="mt-2 max-w-md text-sm text-zinc-400"><?= e($game['format']) ?></p>
</div>
<div class="relative flex flex-col items-start justify-center border-t border-white/[.06] px-5 py-7 sm:px-10 @3xl/dash:items-end @3xl/dash:border-t-0 @3xl/dash:border-l @3xl/dash:text-right">
<div class="num text-6xl leading-none sm:text-8xl <?= e($style['text']) ?>"><?= e($youScore) ?></div>
<p class="mt-2 text-sm text-zinc-400"><?= e($game['avg']) ?></p>
</div>
</div>
<?php endif; ?>
<div class="relative flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t border-white/[.06] px-5 py-3.5 sm:px-8">
<p class="flex items-center gap-2 text-xs text-zinc-400"><?= dartiqo_i('clock', 'size-3.5 text-zinc-500') ?><?= e($game['place']) ?> · <?= e($game['meta']) ?></p>
<?php if ($versus): ?><p class="text-xs text-zinc-500"><?= e($game['format']) ?></p><?php endif; ?>
</div>
<?php if ($heroKpis !== []): ?>
<dl class="relative grid grid-cols-2 gap-px border-t border-white/[.06] bg-white/[.06] sm:grid-cols-4">
<?php foreach ($heroKpis as [$label, $value, $detail]): ?>
<div class="bg-zinc-900/95 px-5 py-4 sm:px-7">
<dt class="eyebrow tracking-[.12em]"><?= e($label) ?></dt>
<dd class="num mt-2 text-2xl leading-none text-zinc-50"><?= e($value) ?></dd>
<dd class="mt-1.5 text-[11px] text-zinc-500"><?= e($detail) ?></dd>
</div>
<?php endforeach; ?>
</dl>
<?php endif; ?>
</section>

<div class="mt-4 grid items-start gap-4 @4xl/dash:grid-cols-[1.15fr_.85fr]">
<section class="panel" aria-labelledby="steps-title">
<div class="mb-5 flex items-end justify-between gap-3">
<h2 id="steps-title"><?= e($game['stepsTitle']) ?></h2>
<span class="chip bg-white/[.06] text-zinc-300"><?= e((string) $stepCount) ?> <?= e($stepWord) ?></span>
</div>
<ol class="relative flex flex-col gap-2 before:absolute before:top-4 before:bottom-4 before:left-[1.35rem] before:w-px before:bg-white/[.06] before:content-['']">
<?php foreach ($game['steps'] as $step): ?>
<?php $stepStyle = dartiqo_result((string) $step['result']); ?>
<li class="relative grid grid-cols-[2.75rem_1fr] items-center gap-3">
<span class="grid size-11 place-items-center rounded-xl bg-zinc-900 text-sm font-bold <?= e($stepStyle['soft']) ?>" aria-hidden="true"><?= e($stepStyle['mark']) ?></span>
<div class="tile flex items-center justify-between gap-3 px-4 py-3">
<span class="min-w-0">
<strong class="block text-sm font-semibold"><?= e($step['title']) ?></strong>
<small class="mt-0.5 block truncate text-xs text-zinc-500"><?= e($step['meta']) ?></small>
</span>
<span class="shrink-0 text-right">
<b class="num block text-lg leading-none"><?= e($step['score']) ?></b>
<small class="mt-1 block text-[10px] font-semibold tracking-wide uppercase <?= e($stepStyle['text']) ?>"><?= e($step['label']) ?></small>
</span>
</div>
</li>
<?php endforeach; ?>
</ol>
</section>

<div class="flex flex-col gap-4">
<?php if ($moreKpis !== []): ?>
<section class="panel" aria-labelledby="more-stats">
<h2 class="mb-4" id="more-stats">Další čísla</h2>
<div class="grid grid-cols-2 gap-2">
<?php foreach ($moreKpis as [$label, $value, $detail]): ?>
<div class="tile p-4">
<span class="eyebrow tracking-[.1em]"><?= e($label) ?></span>
<strong class="num mt-2 block text-2xl leading-none"><?= e($value) ?></strong>
<small class="mt-1.5 block text-[11px] text-zinc-500"><?= e($detail) ?></small>
</div>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>

<?php if ($game['doubles'] !== []): ?>
<section class="panel" aria-labelledby="doubles-title">
<h2 class="mb-4" id="doubles-title">Doubly</h2>
<div class="flex flex-col gap-4">
<?php foreach ($game['doubles'] as [$name, $hits, $attempts]): ?>
<?php $rate = $attempts ? (int) round($hits / $attempts * 100) : 0; ?>
<div>
<div class="flex items-baseline justify-between gap-3 text-sm">
<strong class="num"><?= e($name) ?></strong>
<span class="text-xs text-zinc-500"><?= e((string) $hits) ?>/<?= e((string) $attempts) ?> · <b class="num text-sm <?= e($style['text']) ?>"><?= e((string) $rate) ?> %</b></span>
</div>
<div class="meter mt-2"><i class="<?= e($style['bar']) ?>" style="width:<?= e((string) $rate) ?>%"></i></div>
</div>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>
</div>
</div>

<article class="panel mt-4">
<div class="mb-4 flex items-start justify-between gap-3">
<div>
<h2><?= e($game['chartTitle']) ?></h2>
<p class="mt-1 text-xs text-zinc-500"><?= e($game['chartNote']) ?></p>
</div>
<span id="score-total" class="chip bg-white/[.06] text-zinc-300"></span>
</div>
<div class="relative" style="height:16rem">
<canvas id="score-chart" role="img" aria-label="<?= e($game['chartTitle']) ?>"></canvas>
</div>
<div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4" id="score-grid"></div>
</article>

<div class="mt-4 grid gap-4 @4xl/dash:grid-cols-2">
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
<div id="aim-detail" class="tile mt-2 p-4 text-xs text-zinc-500" aria-live="polite">Klepni na číslo na terči pro počty v tomhle zápase.</div>
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
<svg id="heat-board" class="mx-auto aspect-square w-full" viewBox="0 0 500 500" role="img" aria-label="Mapa dopadů šipek v zápase"></svg>
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
<p class="mt-6 text-xs text-zinc-600">Ukázkový detail zápasu. Čísla, heatmapa i soupeř jsou modelové.</p>
<script src="<?= e(asset('js/vendor/chart.umd.min.js')) ?>"></script>
<script type="application/json" id="match-data"><?= json_encode($viz, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php else: ?>
<?php
$tally = ['win' => 0, 'loss' => 0, 'draw' => 0, 'train' => 0];
foreach ($games as $row) {
    $key = (string) $row['result'];
    if (isset($tally[$key])) {
        $tally[$key]++;
    }
}
$played = $tally['win'] + $tally['loss'] + $tally['draw'];
$winRate = $played > 0 ? (int) round($tally['win'] / $played * 100) : 0;
$formGames = array_slice(array_values(array_filter($games, static fn (array $row): bool => $row['result'] !== 'train')), 0, 5);
$balanceTiles = [
    ['win', 'výhry', 'bg-emerald-400/[.07] ring-1 ring-emerald-400/20 ring-inset', 'text-emerald-300'],
    ['loss', 'prohry', 'bg-rose-400/[.07] ring-1 ring-rose-400/20 ring-inset', 'text-rose-300'],
    ['draw', 'remízy', 'bg-amber-300/[.07] ring-1 ring-amber-300/20 ring-inset', 'text-amber-200'],
];
$ring = 2 * M_PI * 42;
?>
<header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="eyebrow">Archiv</p>
<h1 class="mt-2">Moje hry</h1>
<p class="mt-1 max-w-xl text-sm text-zinc-400">Výhra, remíza, prohra i trénink. Otevři hru a uvidíš skóre, legy a terč.</p>
</div>
<a class="pill" href="<?= e(url('/app/statistics')) ?>"><?= dartiqo_i('chart') ?>Statistiky</a>
</header>

<div class="grid items-start gap-6 @4xl/dash:grid-cols-[minmax(0,1.25fr)_minmax(18rem,.75fr)]">
<div class="flex min-w-0 flex-col gap-4" id="games-page">
<div class="flex flex-wrap items-center gap-3">
<div class="seg shrink-0" role="group" aria-label="Výsledek">
<?php foreach ($filters as $index => [$id, $label, $dot]): ?>
<button class="<?= $index === 0 ? 'active' : '' ?>" type="button" data-result="<?= e($id) ?>"><?php if ($dot !== ''): ?><i class="size-1.5 rounded-full <?= e($dot) ?>"></i><?php endif; ?><?= e($label) ?></button>
<?php endforeach; ?>
</div>
<label class="relative m-0! block min-w-56 flex-1">
<span class="sr-only">Hledat hru</span>
<?= dartiqo_i('search', 'pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-zinc-500') ?>
<input class="h-11! rounded-full! pl-11!" id="games-query" type="search" placeholder="Hledat soupeře nebo režim" autocomplete="off">
</label>
</div>
<p class="text-xs text-zinc-500" id="games-count"><?= e((string) count($games)) ?> her</p>
<div class="flex flex-col gap-2" id="games-list">
<?php foreach ($games as $game): ?>
<?php $attrs = 'data-game data-result="' . e($game['result']) . '" data-search="' . e(mb_strtolower($game['title'] . ' ' . $game['mode'] . ' ' . $game['meta'], 'UTF-8')) . '"'; ?>
<?php require __DIR__ . '/../partials/game-row.php'; ?>
<?php endforeach; ?>
<div class="panel py-12! text-center" id="games-empty" hidden>
<span class="mx-auto grid size-12 place-items-center rounded-2xl bg-white/[.05] text-zinc-500"><?= dartiqo_i('search', 'size-5') ?></span>
<p class="mt-4 text-sm text-zinc-400">Tomuto filtru neodpovídá žádná hra.</p>
</div>
</div>
<p class="mt-2 text-xs text-zinc-600">Náhled archivu. Soupeři i výsledky jsou ukázkové.</p>
</div>

<aside class="flex w-full flex-col gap-4 @4xl/dash:sticky @4xl/dash:top-8">
<section class="panel" aria-labelledby="balance-title">
<span class="pointer-events-none absolute -top-20 -right-20 size-60 rounded-full bg-emerald-400/10 blur-3xl" aria-hidden="true"></span>
<p class="eyebrow">Bilance</p>
<h2 class="sr-only" id="balance-title">Bilance</h2>
<div class="relative mt-4 flex items-center gap-5">
<svg class="size-28 shrink-0 -rotate-90" viewBox="0 0 100 100" aria-hidden="true">
<circle cx="50" cy="50" r="42" fill="none" stroke="rgb(255 255 255 / .07)" stroke-width="9"/>
<circle cx="50" cy="50" r="42" fill="none" stroke="#34d399" stroke-width="9" stroke-linecap="round" stroke-dasharray="<?= e((string) round($ring * $winRate / 100, 2)) ?> <?= e((string) round($ring, 2)) ?>"/>
</svg>
<div>
<strong class="num block text-5xl leading-none text-emerald-300"><?= e((string) $winRate) ?> %</strong>
<p class="mt-2 text-sm text-zinc-400">vyhraných zápasů</p>
<p class="text-xs text-zinc-500"><?= e((string) $tally['win']) ?> z <?= e((string) $played) ?></p>
</div>
</div>
<?php if ($played > 0): ?>
<div class="relative mt-5 flex h-2 gap-0.5 overflow-hidden rounded-full" role="img" aria-label="<?= e((string) $tally['win']) ?> výher, <?= e((string) $tally['loss']) ?> proher, <?= e((string) $tally['draw']) ?> remíz">
<?php if ($tally['win'] > 0): ?><i class="h-full rounded-full bg-emerald-400" style="flex:<?= (int) $tally['win'] ?>"></i><?php endif; ?>
<?php if ($tally['loss'] > 0): ?><i class="h-full rounded-full bg-rose-400" style="flex:<?= (int) $tally['loss'] ?>"></i><?php endif; ?>
<?php if ($tally['draw'] > 0): ?><i class="h-full rounded-full bg-amber-300" style="flex:<?= (int) $tally['draw'] ?>"></i><?php endif; ?>
</div>
<?php endif; ?>
<div class="relative mt-4 grid grid-cols-3 gap-2 text-center">
<?php foreach ($balanceTiles as [$key, $label, $bg, $color]): ?>
<div class="rounded-2xl px-2 py-3 <?= e($bg) ?>">
<strong class="num block text-2xl <?= e($color) ?>"><?= e((string) $tally[$key]) ?></strong>
<span class="text-[10px] tracking-widest text-zinc-500 uppercase"><?= e($label) ?></span>
</div>
<?php endforeach; ?>
</div>
<?php if ($tally['train'] > 0): ?>
<p class="relative mt-4 flex items-center gap-2 text-xs text-zinc-400"><span class="size-1.5 rounded-full bg-accent"></span>Trénink <b class="text-accent"><?= e((string) $tally['train']) ?></b> · mimo zápasy</p>
<?php endif; ?>
</section>
<section class="panel" aria-labelledby="form-title">
<div class="flex items-center justify-between"><p class="eyebrow" id="form-title">Forma</p><span class="text-xs text-zinc-500">posledních 5</span></div>
<div class="mt-4 flex gap-2" aria-label="Forma výher, remíz a proher">
<?php foreach ($formGames as $row): ?>
<?php $tone = dartiqo_result((string) $row['result']); ?>
<?php $short = explode(' · ', (string) $row['title'])[0]; ?>
<a class="group flex min-w-0 flex-1 flex-col items-center gap-2" href="<?= e(url('/app/games/' . $row['id'])) ?>" title="<?= e($row['title'] . ' · ' . $row['score'] . ' · ' . $row['label']) ?>">
<span class="grid h-11 w-full place-items-center rounded-xl text-xs font-bold transition group-hover:-translate-y-0.5 <?= e($tone['tile']) ?>"><?= e($tone['mark']) ?></span>
<small class="w-full truncate text-center text-[10px] text-zinc-500"><?= e($short) ?></small>
</a>
<?php endforeach; ?>
</div>
</section>
</aside>
</div>
<?php endif; ?>
</main>
