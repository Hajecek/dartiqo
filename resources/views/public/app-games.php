<?php
require __DIR__ . '/../partials/player-games.php';

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
<header class="mb-6">
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Moje hry</p>
<h1>Zápas nenalezen</h1>
<p class="mt-2">Tenhle odkaz nevede na žádnou ukázkovou hru.</p>
</header>
<a class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-zinc-100" href="<?= e(url('/app/games')) ?>">Zpět na hry</a>
<?php elseif ($game !== null): ?>
<?php
$style = dartiqo_result((string) $game['result']);
$versus = preg_match('/^(\S+)\s+:\s+(\S+)$/u', (string) $game['score'], $scoreParts) === 1;
$youScore = $versus ? $scoreParts[1] : (string) $game['score'];
$oppScore = $versus ? $scoreParts[2] : '';
$oppScoreClass = $game['result'] === 'draw' ? $style['text'] : ($game['result'] === 'loss' ? 'text-emerald-300' : 'text-zinc-300');
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
<a class="text-xs font-semibold text-accent" href="<?= e(url('/app/games')) ?>">Všechny hry</a>

<section class="mt-4 overflow-hidden rounded-3xl border border-white/10 bg-zinc-900" aria-labelledby="match-title">
<h1 id="match-title" class="sr-only"><?= e($game['title']) ?>, <?= e($verdict) ?> <?= e($game['score']) ?></h1>
<?php if ($versus): ?>
<div class="relative">
<div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
<div class="absolute top-1/2 left-[38%] size-56 -translate-x-1/2 -translate-y-1/2 rounded-full opacity-20 blur-3xl <?= e($style['bar']) ?>"></div>
<?php if ($oppGlow !== ''): ?><div class="absolute top-1/2 left-[62%] size-56 -translate-x-1/2 -translate-y-1/2 rounded-full opacity-20 blur-3xl <?= e($oppGlow) ?>"></div><?php endif; ?>
</div>
<div class="relative grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center px-4 py-5 sm:px-8 sm:py-8">
<div class="min-w-0 pr-3 text-right sm:pr-7">
<span class="text-[10px] font-bold uppercase tracking-[.22em] text-muted"><?= e($youCaption) ?></span>
<strong class="mt-1 block truncate text-base sm:text-2xl" data-player-full><?= e($youTitle) ?></strong>
<div class="mt-4 font-display text-6xl leading-none tracking-tight sm:text-8xl <?= e($style['text']) ?>"><?= e($youScore) ?></div>
<div class="mt-3 flex items-baseline justify-end gap-2">
<span class="text-[10px] font-bold uppercase tracking-[.16em] text-zinc-500"><?= e($youStatLabel) ?></span>
<span class="font-display text-xl text-zinc-50 sm:text-2xl"><?= e($game['avg']) ?></span>
</div>
</div>
<div class="font-display text-4xl leading-none text-zinc-600 sm:text-6xl" aria-hidden="true">:</div>
<div class="min-w-0 pl-3 sm:pl-7">
<span class="text-[10px] font-bold uppercase tracking-[.22em] text-muted"><?= e($oppCaption) ?></span>
<strong class="mt-1 block truncate text-base sm:text-2xl"><?= e($oppTitle) ?></strong>
<div class="mt-4 font-display text-6xl leading-none tracking-tight sm:text-8xl <?= e($oppScoreClass) ?>"><?= e($oppScore) ?></div>
<div class="mt-3 flex items-baseline gap-2">
<span class="font-display text-xl text-zinc-50 sm:text-2xl"><?= e($oppStat) ?></span>
<span class="text-[10px] font-bold uppercase tracking-[.16em] text-zinc-500"><?= e($oppStatLabel) ?></span>
</div>
</div>
</div>
</div>
<?php else: ?>
<div class="grid sm:grid-cols-[1.3fr_.7fr]">
<div class="bg-gradient-to-r <?= e($style['wash']) ?> to-transparent px-4 py-5 sm:px-8 sm:py-7">
<span class="text-[10px] font-bold uppercase tracking-[.22em] text-muted"><?= e($youCaption) ?></span>
<strong class="mt-3 block text-2xl sm:text-3xl"><?= e($youTitle) ?></strong>
<p class="mt-2 max-w-md text-sm"><?= e($game['format']) ?></p>
</div>
<div class="flex flex-col items-start justify-center border-t border-white/10 px-4 py-5 sm:items-end sm:border-t-0 sm:border-l sm:px-8 sm:text-right">
<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide <?= e($style['tile']) ?>"><b class="grid size-4 place-items-center rounded-full bg-black/15 text-[9px]"><?= e($style['mark']) ?></b><?= e($verdict) ?></span>
<div class="mt-3 font-display text-5xl leading-none tracking-tight sm:text-7xl <?= e($style['text']) ?>"><?= e($youScore) ?></div>
<p class="mt-2 text-sm"><?= e($game['avg']) ?></p>
</div>
</div>
<?php endif; ?>
<div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t border-white/10 px-4 py-3 sm:px-8">
<div class="flex flex-wrap items-center gap-2">
<span class="<?= e(dartiqo_game_tone($game['tone'])) ?>"><?= e($game['mode']) ?></span>
<?php if ($versus): ?>
<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide <?= e($style['tile']) ?>"><b class="grid size-4 place-items-center rounded-full bg-black/15 text-[9px]"><?= e($style['mark']) ?></b><?= e($verdict) ?></span>
<?php endif; ?>
</div>
<p class="text-xs"><?= e($game['place']) ?> · <?= e($game['meta']) ?></p>
</div>
<?php if ($heroKpis !== []): ?>
<dl class="grid grid-cols-2 gap-px border-t border-white/10 bg-white/10 sm:grid-cols-4">
<?php foreach ($heroKpis as [$label, $value, $detail]): ?>
<div class="bg-zinc-900 px-4 py-3.5 sm:px-6">
<dt class="text-[10px] font-bold uppercase tracking-[.18em] text-zinc-500"><?= e($label) ?></dt>
<dd class="mt-1 font-display text-xl leading-none text-zinc-50"><?= e($value) ?></dd>
<dd class="mt-1 text-[11px] text-zinc-500"><?= e($detail) ?></dd>
</div>
<?php endforeach; ?>
</dl>
<?php endif; ?>
</section>

<div class="mt-4 grid items-start gap-4 lg:grid-cols-[1.15fr_.85fr]">
<section class="rounded-3xl border border-white/10 bg-zinc-900 p-5 sm:p-6" aria-labelledby="steps-title">
<div class="mb-4 flex items-end justify-between gap-3">
<h2 id="steps-title"><?= e($game['stepsTitle']) ?></h2>
<p class="text-xs text-zinc-500"><?= e((string) $stepCount) ?> <?= e($stepWord) ?></p>
</div>
<ol class="flex flex-col gap-2">
<?php foreach ($game['steps'] as $step): ?>
<?php $stepStyle = dartiqo_result((string) $step['result']); ?>
<li class="grid grid-cols-[2.75rem_1fr] items-stretch gap-3">
<span class="grid place-items-center rounded-2xl text-sm font-bold <?= e($stepStyle['tile']) ?>" aria-hidden="true"><?= e($stepStyle['mark']) ?></span>
<div class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-black/20 px-4 py-3">
<span class="min-w-0">
<strong class="block text-sm"><?= e($step['title']) ?></strong>
<small class="mt-0.5 block text-xs text-zinc-500"><?= e($step['meta']) ?></small>
</span>
<span class="shrink-0 text-right">
<b class="block font-display text-lg leading-none"><?= e($step['score']) ?></b>
<small class="mt-1 block text-[10px] font-bold uppercase tracking-wide <?= e($stepStyle['text']) ?>"><?= e($step['label']) ?></small>
</span>
</div>
</li>
<?php endforeach; ?>
</ol>
</section>

<div class="flex flex-col gap-4">
<?php if ($moreKpis !== []): ?>
<section class="rounded-3xl border border-white/10 bg-zinc-900 p-5 sm:p-6" aria-labelledby="more-stats">
<h2 class="mb-4" id="more-stats">Další čísla</h2>
<div class="grid grid-cols-2 gap-3">
<?php foreach ($moreKpis as [$label, $value, $detail]): ?>
<div>
<span class="text-[10px] uppercase tracking-widest text-zinc-500"><?= e($label) ?></span>
<strong class="mt-1 block font-display text-2xl"><?= e($value) ?></strong>
<small class="text-[11px] text-zinc-500"><?= e($detail) ?></small>
</div>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>

<?php if ($game['doubles'] !== []): ?>
<section class="rounded-3xl border border-white/10 bg-zinc-900 p-5 sm:p-6" aria-labelledby="doubles-title">
<h2 class="mb-4" id="doubles-title">Doubly</h2>
<div class="flex flex-col gap-3">
<?php foreach ($game['doubles'] as [$name, $hits, $attempts]): ?>
<?php $rate = $attempts ? (int) round($hits / $attempts * 100) : 0; ?>
<div>
<div class="flex items-baseline justify-between gap-3 text-sm">
<strong><?= e($name) ?></strong>
<span class="text-zinc-400"><?= e((string) $hits) ?>/<?= e((string) $attempts) ?> · <b class="<?= e($style['text']) ?>"><?= e((string) $rate) ?> %</b></span>
</div>
<div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10"><i class="block h-full rounded-full <?= e($style['bar']) ?>" style="width:<?= e((string) $rate) ?>%"></i></div>
</div>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>
</div>
</div>

<article class="mt-4 rounded-3xl border border-white/10 bg-zinc-900 p-5 sm:p-6">
<div class="mb-4 flex items-end justify-between gap-3">
<div>
<h2><?= e($game['chartTitle']) ?></h2>
<p class="mt-1 text-xs"><?= e($game['chartNote']) ?></p>
</div>
<span id="score-total" class="text-xs text-zinc-400"></span>
</div>
<div class="relative" style="height:16rem">
<canvas id="score-chart" role="img" aria-label="<?= e($game['chartTitle']) ?>"></canvas>
</div>
<div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4" id="score-grid"></div>
</article>

<div class="mt-4 grid gap-4 lg:grid-cols-2">
<article class="rounded-3xl border border-white/10 bg-[#121722] p-5 sm:p-6">
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
<p id="aim-detail" class="mt-3 text-xs text-zinc-400" aria-live="polite">Klepni na výseč pro počty v tomhle zápase.</p>
</article>
<article class="rounded-3xl border border-white/10 bg-zinc-900 p-5 sm:p-6">
<div class="mb-2 flex items-start justify-between gap-3">
<div>
<h3>Mapa dopadů</h3>
<p class="mt-1 text-xs">Každá tečka je hod z téhle hry.</p>
</div>
<div class="flex gap-2">
<button class="flex size-9 items-center justify-center rounded-full border border-white/10 bg-transparent text-lg text-zinc-100" type="button" data-heat-zoom="-0.35" aria-label="Oddálit mapu">−</button>
<button class="flex size-9 items-center justify-center rounded-full border border-white/10 bg-transparent text-lg text-zinc-100" type="button" data-heat-zoom="0.35" aria-label="Přiblížit mapu">+</button>
</div>
</div>
<div class="rounded-3xl bg-black p-2">
<svg id="heat-board" class="mx-auto aspect-square w-full" viewBox="0 0 500 500" role="img" aria-label="Mapa dopadů šipek v zápase"></svg>
</div>
<div class="mt-4 flex flex-col gap-2.5" id="map-shares"></div>
<h3 class="mt-5">Nejčastější pole</h3>
<div class="mt-3 grid grid-cols-2 gap-2" id="map-fields"></div>
</article>
</div>
<p class="mt-6 text-xs text-zinc-500">Ukázkový detail zápasu. Čísla, heatmapa i soupeř jsou modelové.</p>
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
$balance = [
    ['win', 'Výhry', (string) $tally['win']],
    ['loss', 'Prohry', (string) $tally['loss']],
    ['draw', 'Remízy', (string) $tally['draw']],
    ['train', 'Trénink', (string) $tally['train']],
];
?>
<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Archiv</p>
<h1>Moje hry</h1>
<p class="mt-2 max-w-xl text-sm">Výhra, remíza, prohra i trénink. Otevři hru a uvidíš skóre, legy a terč.</p>
</div>
<a class="text-xs font-semibold text-accent" href="<?= e(url('/app/statistics')) ?>">Statistiky</a>
</header>

<div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,.8fr)]">
<div class="mb-5 flex min-w-0 flex-col gap-3" id="games-page">
<div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
<div class="flex flex-wrap gap-2" role="group" aria-label="Výsledek">
<?php foreach ($filters as $index => [$id, $label, $dot]): ?>
<button class="inline-flex items-center gap-1.5 rounded-full border border-line bg-transparent px-4 py-2 text-xs text-muted [&.active]:border-accent [&.active]:bg-accent [&.active]:text-zinc-900<?= $index === 0 ? ' active' : '' ?>" type="button" data-result="<?= e($id) ?>"><?php if ($dot !== ''): ?><i class="size-1.5 rounded-full <?= e($dot) ?>"></i><?php endif; ?><?= e($label) ?></button>
<?php endforeach; ?>
</div>
<input class="lg:w-64" id="games-query" type="search" placeholder="Hledat soupeře nebo režim" aria-label="Hledat hru" autocomplete="off">
</div>
<p class="text-xs text-zinc-500" id="games-count"><?= e((string) count($games)) ?> her</p>
<div class="flex flex-col gap-2" id="games-list">
<?php foreach ($games as $game): ?>
<?php $attrs = 'data-game data-result="' . e($game['result']) . '" data-search="' . e(mb_strtolower($game['title'] . ' ' . $game['mode'] . ' ' . $game['meta'], 'UTF-8')) . '"'; ?>
<?php require __DIR__ . '/../partials/game-row.php'; ?>
<?php endforeach; ?>
<p class="py-8 text-center text-sm text-zinc-500" id="games-empty" hidden>Tomuto filtru neodpovídá žádná hra.</p>
</div>
<p class="mt-6 text-xs text-zinc-500">Náhled archivu. Soupeři i výsledky jsou ukázkové.</p>
</div>
<aside class="flex w-full flex-col gap-4 lg:sticky lg:top-8">
<section class="rounded-3xl border border-white/10 bg-zinc-900 p-5" aria-labelledby="balance-title">
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Souhrn</p>
<h2 class="mt-1" id="balance-title">Bilance</h2>
<div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4">
<?php foreach ($balance as [$result, $label, $count]): ?>
<?php $tone = dartiqo_result($result); ?>
<div>
<span class="text-[10px] font-bold uppercase tracking-[.18em] text-zinc-500"><?= e($label) ?></span>
<strong class="mt-1 block font-display text-3xl <?= e($tone['text']) ?>"><?= e($count) ?></strong>
</div>
<?php endforeach; ?>
</div>
<p class="mt-4 text-sm"><?= e((string) $winRate) ?> % vyhraných zápasů, <?= e((string) $tally['win']) ?> z <?= e((string) $played) ?>.</p>
</section>
<section class="rounded-3xl border border-white/10 bg-zinc-900 p-5">
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Forma</p>
<p class="mt-1 text-xs">posledních pět zápasů</p>
<div class="mt-4 flex gap-2" aria-label="Forma výher, remíz a proher">
<?php foreach ($formGames as $row): ?>
<?php $tone = dartiqo_result((string) $row['result']); ?>
<a class="grid size-9 place-items-center rounded-full text-xs font-bold <?= e($tone['tile']) ?>" href="<?= e(url('/app/games/' . $row['id'])) ?>" title="<?= e($row['title'] . ' · ' . $row['label']) ?>"><?= e($tone['mark']) ?></a>
<?php endforeach; ?>
</div>
<a class="mt-5 inline-flex text-xs font-semibold text-accent" href="<?= e(url('/app/statistics')) ?>">Všechny statistiky</a>
</section>
</aside>
</div>
<?php endif; ?>
</main>
