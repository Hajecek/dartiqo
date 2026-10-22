<?php
require_once __DIR__ . '/../partials/ui.php';

$kpis = [
    ['Průměr', '#faff00'],
    ['Výhry', '#34d399'],
    ['Zavírání', '#38bdf8'],
    ['Maximum 180', '#f472b6'],
];
$steps = [
    ['Otevři aplikaci Dartiqo', 'a přihlas se stejným účtem jako tady'],
    ['Spusť zápas', 'proti botovi, kamarádovi nebo sám'],
    ['Sleduj ho živě', 'skóre naskočí tady po každé šipce'],
];
?>
<main class="@container/dash mx-auto w-full max-w-7xl">
<header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="eyebrow"><?= e(dartiqo_today_label()) ?></p>
<h1 class="mt-2">Ahoj, <span data-player-first></span><span class="text-accent">.</span></h1>
<p class="mt-1 text-sm text-zinc-400">Spusť zápas v aplikaci a skóre uvidíš tady živě.</p>
</div>
<div class="flex flex-wrap items-center gap-2">
<?= dartiqo_theme_toggle() ?>
<button class="icon-btn text-rose-400 hover:bg-rose-400/10 hover:text-rose-300" type="button" data-logout aria-label="Odhlásit se" title="Odhlásit se"><?= dartiqo_i('logout') ?></button>
</div>
</header>

<div id="live-hero" class="mb-4" data-href="<?= e(url('/app/live')) ?>" hidden></div>

<div class="grid gap-4 @4xl/dash:grid-cols-12">

<a class="panel panel-link group p-0! @4xl/dash:col-span-8" href="<?= e(url('/app/profile')) ?>">
<span class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-accent/15 blur-3xl transition-opacity group-hover:opacity-80" aria-hidden="true"></span>
<div class="relative flex h-full flex-col gap-6 p-5 sm:p-7">
<div class="flex items-center gap-4">
<span class="relative shrink-0">
<span class="grid size-16 place-items-center overflow-hidden rounded-2xl bg-accent text-xl font-bold text-black shadow-[0_10px_30px_-10px_#faff00aa] [&_img]:size-full [&_img]:object-cover" data-player-avatar></span>
<span class="absolute -right-1.5 -bottom-1.5 grid size-7 place-items-center rounded-lg border-2 border-zinc-900 bg-zinc-100 text-[11px] font-bold text-zinc-950">1</span>
</span>
<span class="min-w-0 flex-1">
<strong class="block truncate text-lg font-semibold" data-player-full></strong>
<span class="mt-1 block text-xs text-zinc-400" data-player-user></span>
</span>
<span class="icon-btn hidden sm:grid" aria-hidden="true"><?= dartiqo_i('arrow') ?></span>
</div>
<div class="mt-auto">
<span class="eyebrow">Celkový průměr</span>
<strong class="num mt-1 block text-6xl leading-none text-zinc-600 sm:text-7xl">–</strong>
<p class="mt-2 text-xs text-zinc-500">Ukáže se po prvních odehraných zápasech.</p>
</div>
<div>
<div class="mb-2 flex items-center justify-between text-xs"><span class="text-zinc-400">Level 1 → 2</span><span class="num text-zinc-300">0 <span class="text-zinc-500">/ 1 000 XP</span></span></div>
<div class="meter h-2"><i class="bg-accent" style="width:0%"></i></div>
</div>
</div>
</a>

<section class="relative isolate flex flex-col overflow-hidden rounded-[28px] bg-accent p-5 text-black shadow-[0_24px_60px_-28px_#faff00aa] sm:p-7 @4xl/dash:col-span-4" aria-labelledby="start-title">
<span class="pointer-events-none absolute -right-10 -bottom-10 size-48 rounded-full border-[18px] border-black/[.06]" aria-hidden="true"></span>
<span class="pointer-events-none absolute -right-2 -bottom-2 size-24 rounded-full border-[12px] border-black/[.06]" aria-hidden="true"></span>
<span class="text-[11px] font-semibold tracking-[.16em] uppercase opacity-60">Začni hrát</span>
<h2 class="mt-3 text-2xl font-semibold" id="start-title">Propoj aplikaci</h2>
<ol class="relative mt-5 flex flex-col gap-3">
<?php foreach ($steps as $i => [$title, $text]): ?>
<li class="flex gap-3"><b class="grid size-7 shrink-0 place-items-center rounded-full bg-black text-xs text-accent"><?= $i + 1 ?></b><span class="text-sm"><strong class="block font-semibold"><?= e($title) ?></strong><span class="opacity-70"><?= e($text) ?></span></span></li>
<?php endforeach; ?>
</ol>
</section>

<section class="grid grid-cols-2 gap-4 @4xl/dash:col-span-12 @4xl/dash:grid-cols-4" aria-label="Základní statistiky">
<?php foreach ($kpis as [$label, $color]): ?>
<article class="panel flex flex-col gap-4 p-4! sm:p-5!">
<div class="flex items-center justify-between gap-2"><span class="eyebrow tracking-[.12em]"><?= e($label) ?></span><span class="size-1.5 rounded-full opacity-40" style="background:<?= e($color) ?>"></span></div>
<strong class="num text-3xl leading-none text-zinc-600 sm:text-4xl">–</strong>
<small class="text-xs text-zinc-500">Zatím bez zápasů</small>
</article>
<?php endforeach; ?>
</section>

<section class="panel @4xl/dash:col-span-8" aria-labelledby="recent-title">
<p class="eyebrow">Naposledy</p>
<h2 class="mt-1" id="recent-title">Poslední hry</h2>
<div class="mt-5 flex flex-col items-center gap-3 rounded-2xl border border-dashed border-white/10 px-6 py-10 text-center">
<span class="grid size-12 place-items-center rounded-2xl bg-white/[.04] text-zinc-500 [&_svg]:size-5"><?= dartiqo_i('clock') ?></span>
<strong class="text-sm">Zatím žádné hry</strong>
<p class="max-w-sm text-xs text-zinc-500">Dohrané zápasy z aplikace se tu objeví, až se začnou synchronizovat na web.</p>
</div>
</section>

<section class="panel @4xl/dash:col-span-4" aria-labelledby="form-title">
<div class="flex items-center justify-between"><p class="eyebrow" id="form-title">Forma</p><span class="text-xs text-zinc-500">posledních 5</span></div>
<div class="mt-4 flex gap-2" aria-hidden="true">
<?php for ($i = 0; $i < 5; $i++): ?>
<i class="h-11 flex-1 rounded-xl border border-dashed border-white/10"></i>
<?php endfor; ?>
</div>
<p class="mt-3 text-xs text-zinc-500">Forma se spočítá z pěti posledních zápasů.</p>
</section>

</div>
</main>
