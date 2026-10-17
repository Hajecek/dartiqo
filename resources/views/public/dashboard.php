<?php
require __DIR__ . '/../partials/player-games.php';

$weekdays = ['NE', 'PO', 'ÚT', 'ST', 'ČT', 'PÁ', 'SO'];
$streakDays = [];
for ($ago = 6; $ago >= 0; $ago--) {
    $ts = strtotime('-' . $ago . ' day');
    $streakDays[] = [
        'label' => $weekdays[(int) date('w', $ts)],
        'trained' => $ago >= 1 && $ago <= 4,
        'today' => $ago === 0,
    ];
}
$recent = array_slice(dartiqo_games(), 0, 3);
$form = [1, 0, 1, 1, 0];
$svg = static function (string $d): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' . $d . '"/></svg>';
};
?>
<main class="@container/dash mx-auto w-full max-w-7xl">
<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Tvůj účet</p>
<h1>Ahoj, <span data-player-first>Alex</span>.</h1>
</div>
<div class="flex flex-wrap gap-2">
<button class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-zinc-100 [&_svg]:size-4 [&_b]:rounded-full [&_b]:bg-accent [&_b]:px-1.5 [&_b]:text-[10px] [&_b]:font-bold [&_b]:text-black" type="button" data-sheet="sheet-daily"><?= $svg('M5 21V4h9l-1.2 4H19l-8 13h1.2L5 21Z') ?><span>Denní výzva</span><b>1</b></button>
<button class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-zinc-100 [&_svg]:size-4 [&_b]:rounded-full [&_b]:bg-accent [&_b]:px-1.5 [&_b]:text-[10px] [&_b]:font-bold [&_b]:text-black" type="button" data-sheet="sheet-streak"><?= $svg('M12 3s4 4.2 4 8a4 4 0 0 1-8 0c0-1.2.6-2.2 1.2-3.1C8.4 10 8 12 9.2 13.2 8.4 9.5 12 6.2 12 3Z') ?><span>Série</span><b>4</b></button>
<a class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-zinc-100 [&_svg]:size-4 aspect-square px-2.5" href="<?= e(url('/app/settings')) ?>" aria-label="Nastavení"><?= $svg('M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Z M12 3.5v2 M12 18.5v2 M4.8 6.2l1.5 1.5 M17.7 16.3l1.5 1.5 M3.5 12h2 M18.5 12h2 M4.8 17.8l1.5-1.5 M17.7 7.7l1.5-1.5') ?></a>
</div>
</header>

<a class="mb-6 flex items-center gap-4 rounded-3xl border border-white/10 bg-zinc-900 p-4" href="<?= e(url('/app/profile')) ?>">
<span class="grid size-14 shrink-0 place-items-center rounded-full bg-accent text-lg font-bold text-black" data-player-avatar>A</span>
<span class="min-w-0 flex-1 [&_strong]:block">
<strong data-player-full>Alex Novák</strong>
<span class="mt-1 inline-flex items-center gap-2 text-xs [&_b]:grid [&_b]:size-8 [&_b]:place-items-center [&_b]:rounded-lg [&_b]:bg-accent [&_b]:font-display [&_b]:text-sm [&_b]:text-black [&_small]:text-zinc-400"><b>12</b><span><small>Level · Expert</small><span class="mt-1 block h-1 w-24 overflow-hidden rounded-full bg-white/10 [&_i]:block [&_i]:h-full [&_i]:bg-accent"><i style="width:64%"></i></span></span></span>
</span>
<span class="text-right [&_small]:text-[10px] [&_small]:uppercase [&_small]:text-zinc-500 [&_strong]:block [&_strong]:font-display [&_strong]:text-3xl [&_span]:text-[10px] [&_span]:text-zinc-500">
<small>Ø</small>
<strong>56.8</strong>
<span>celkový průměr</span>
</span>
</a>

<section class="grid gap-3 sm:grid-cols-3" aria-label="Dnes">
<button class="rounded-3xl border border-white/10 bg-zinc-900 p-4 text-left" type="button" data-sheet="sheet-daily">
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Denní výzva</p>
<strong class="mt-2 block font-display text-3xl">2 <span class="text-xl text-zinc-500">/ 3</span></strong>
<p class="mt-1 text-xs">Zbývá Kolem hodin.</p>
</button>
<button class="rounded-3xl border border-white/10 bg-zinc-900 p-4 text-left" type="button" data-sheet="sheet-streak">
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Série</p>
<strong class="mt-2 block font-display text-3xl">4 <span class="text-xl text-zinc-500">dny</span></strong>
<p class="mt-1 text-xs">Dnešek ještě chybí.</p>
</button>
<a class="rounded-3xl border border-white/10 bg-zinc-900 p-4" href="<?= e(url('/app/games/dartbot-18')) ?>">
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Poslední zápas</p>
<strong class="mt-2 block font-display text-3xl">3 : 1</strong>
<p class="mt-1 text-xs">DartBot · úroveň 4 · výhra</p>
</a>
</section>

<section class="mt-8" aria-labelledby="snap-title">
<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Posledních 30 dní</p>
<h2 id="snap-title">Základní statistiky</h2>
</div>
<a class="text-xs font-semibold text-accent" href="<?= e(url('/app/statistics')) ?>">Všechny statistiky</a>
</div>
<div class="grid grid-cols-2 gap-3 @3xl/dash:grid-cols-4">
<article class="rounded-2xl border border-white/10 bg-zinc-900 p-4"><span class="text-[10px] uppercase tracking-widest text-zinc-400">Průměr</span><strong class="mt-2 block font-display text-2xl">58.4</strong><small class="text-xs text-accent">+2.4 za 30 dní</small></article>
<article class="rounded-2xl border border-white/10 bg-zinc-900 p-4"><span class="text-[10px] uppercase tracking-widest text-zinc-400">Výhry</span><strong class="mt-2 block font-display text-2xl text-emerald-400">64 %</strong><small class="text-xs text-zinc-500">18 z 28 zápasů</small></article>
<article class="rounded-2xl border border-white/10 bg-zinc-900 p-4"><span class="text-[10px] uppercase tracking-widest text-zinc-400">Zavírání</span><strong class="mt-2 block font-display text-2xl text-accent">32 %</strong><small class="text-xs text-zinc-500">18 z 56 na double</small></article>
<article class="rounded-2xl border border-white/10 bg-zinc-900 p-4"><span class="text-[10px] uppercase tracking-widest text-zinc-400">180</span><strong class="mt-2 block font-display text-2xl">9</strong><small class="text-xs text-zinc-500">kol s maximem</small></article>
</div>
<div class="mt-4 flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-zinc-900 px-4 py-3">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Forma</p>
<p class="mt-1 text-xs">posledních pět zápasů</p>
</div>
<div class="flex gap-1.5" aria-label="Forma výher a proher">
<?php foreach ($form as $won): ?>
<i class="grid size-7 place-items-center rounded-full text-[10px] font-bold <?= $won ? 'bg-emerald-400 text-black' : 'bg-white/10 text-zinc-500' ?>"><?= $won ? 'V' : 'P' ?></i>
<?php endforeach; ?>
</div>
</div>
</section>

<section class="mt-8" aria-labelledby="recent-title">
<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Naposledy</p>
<h2 id="recent-title">Poslední hry</h2>
</div>
<a class="text-xs font-semibold text-accent" href="<?= e(url('/app/games')) ?>">Všechny hry</a>
</div>
<div class="flex flex-col gap-2">
<?php foreach ($recent as $game): ?>
<?php $attrs = ''; require __DIR__ . '/../partials/game-row.php'; ?>
<?php endforeach; ?>
</div>
</section>
</main>

<dialog class="m-auto max-h-[min(860px,calc(100svh-2rem))] w-[min(560px,calc(100%-2rem))] overflow-auto rounded-3xl border border-white/10 bg-zinc-950 p-5 text-left text-zinc-100 shadow-2xl backdrop:bg-black/70" id="sheet-daily">
<div class="mb-3 flex items-center justify-between gap-3"><p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Dnes</p><button class="flex size-9 items-center justify-center rounded-full border border-line bg-transparent text-ink" type="button" data-sheet-close aria-label="Zavřít">×</button></div>
<div class="rounded-3xl bg-accent p-6 text-black [&_p]:text-black/75! [&_strong]:block [&_strong]:font-display [&_strong]:text-5xl [&_strong]:leading-none [&_small]:text-2xl">
<span>Denní výzva</span>
<strong>2 <small>/ 3</small></strong>
<p>Splň všechny tři v aplikaci. Za hotový úkol přibude 50 XP.</p>
<div class="mt-4 flex gap-2 [&_i]:h-1.5 [&_i]:flex-1 [&_i]:rounded-full [&_i]:bg-black/20 [&_i.on]:bg-black"><i class="on"></i><i class="on"></i><i></i></div>
</div>
<div class="mt-3 flex items-center gap-3 rounded-2xl border border-white/10 bg-zinc-900 p-3 [&.done]:opacity-60 [&_b]:grid [&_b]:size-8 [&_b]:shrink-0 [&_b]:place-items-center [&_b]:rounded-full [&_b]:bg-white/10 [&_span]:min-w-0 [&_span]:flex-1 [&_small]:block [&_small]:text-xs [&_small]:text-zinc-500 done"><b>✓</b><span><strong>Tři T20 v kole</strong><small>Hotovo · zásah 180</small></span></div>
<div class="mt-3 flex items-center gap-3 rounded-2xl border border-white/10 bg-zinc-900 p-3 [&.done]:opacity-60 [&_b]:grid [&_b]:size-8 [&_b]:shrink-0 [&_b]:place-items-center [&_b]:rounded-full [&_b]:bg-white/10 [&_span]:min-w-0 [&_span]:flex-1 [&_small]:block [&_small]:text-xs [&_small]:text-zinc-500 done"><b>✓</b><span><strong>Checkout 40</strong><small>Hotovo · D20</small></span></div>
<div class="mt-3 flex items-center gap-3 rounded-2xl border border-accent/40 bg-zinc-900 p-3 [&_b]:grid [&_b]:size-8 [&_b]:shrink-0 [&_b]:place-items-center [&_b]:rounded-full [&_b]:bg-white/10 [&_span]:min-w-0 [&_span]:flex-1 [&_small]:block [&_small]:text-xs [&_small]:text-zinc-500"><b>◎</b><span><strong>Kolem hodin</strong><small>Projdi 1–20 a bull · v aplikaci</small></span></div>
</dialog>

<dialog class="m-auto max-h-[min(860px,calc(100svh-2rem))] w-[min(560px,calc(100%-2rem))] overflow-auto rounded-3xl border border-white/10 bg-zinc-950 p-5 text-left text-zinc-100 shadow-2xl backdrop:bg-black/70" id="sheet-streak">
<div class="mb-3 flex items-center justify-between gap-3"><p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Série</p><button class="flex size-9 items-center justify-center rounded-full border border-line bg-transparent text-ink" type="button" data-sheet-close aria-label="Zavřít">×</button></div>
<div class="rounded-3xl bg-accent p-6 text-black [&_p]:text-black/75! [&_strong]:block [&_strong]:font-display [&_strong]:text-5xl [&_strong]:leading-none [&_small]:text-2xl">
<span>Série</span>
<strong>4 <small>dny</small></strong>
<p>Dnešek ještě chybí. Sérii udržíš tréninkem v aplikaci.</p>
<div class="mt-4 grid grid-cols-7 gap-1 text-center [&_small]:block [&_small]:text-[9px] [&_i]:mx-auto [&_i]:mt-1 [&_i]:grid [&_i]:size-8 [&_i]:place-items-center [&_i]:rounded-full [&_i]:bg-black/10 [&_i.on]:bg-black [&_i.on]:text-accent [&_.today_i]:ring-2 [&_.today_i]:ring-black">
<?php foreach ($streakDays as $day): ?>
<div class="<?= $day['today'] ? 'today' : '' ?>">
<small><?= e($day['label']) ?></small>
<i class="<?= $day['trained'] ? 'on' : '' ?>"><?= $day['trained'] ? '✓' : '' ?></i>
</div>
<?php endforeach; ?>
</div>
</div>
</dialog>
