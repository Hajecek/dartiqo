<?php
require_once __DIR__ . '/../partials/ui.php';
?>
<main class="mx-auto w-full max-w-7xl [&:fullscreen]:max-w-none [&:fullscreen]:overflow-auto [&:fullscreen]:bg-zinc-950 [&:fullscreen]:p-6 sm:[&:fullscreen]:p-10" id="live-page">
<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
<div class="min-w-0">
<p class="eyebrow flex flex-wrap items-center gap-2"><span id="live-badge" class="chip bg-white/[.06] text-zinc-400">Připojuji…</span><span id="live-meta"></span></p>
<h1 class="mt-2 truncate" id="live-title">Živý zápas<span class="text-accent">.</span></h1>
</div>
<div class="flex flex-wrap items-center gap-2">
<button class="btn-ghost whitespace-nowrap" type="button" data-live-fullscreen><?= dartiqo_i('expand') ?><span>Celá obrazovka</span></button>
</div>
</header>

<section class="panel flex flex-col items-center gap-4 py-16! text-center" id="live-empty">
<span class="grid size-16 place-items-center rounded-3xl bg-accent/10 text-accent ring-1 ring-accent/25 ring-inset [&_svg]:size-7"><?= dartiqo_i('target') ?></span>
<h2>Čekám na zápas z mobilu</h2>
<p class="max-w-md text-sm text-zinc-400">V aplikaci Dartiqo se přihlas ke stejnému účtu (Profil → Nastavení → Dartiqo účet) a spusť zápas. Skóre se tu objeví samo, stránku nech otevřenou.</p>
</section>

<section class="flex flex-col gap-4" id="live-board" hidden>
<div class="grid gap-4" id="live-players"></div>
<div class="grid gap-4 lg:grid-cols-12">
<section class="panel lg:col-span-5" aria-labelledby="live-visit-title">
<div class="flex items-center justify-between gap-3"><p class="eyebrow" id="live-visit-title">Aktuální kolo</p><span class="text-xs text-zinc-500" id="live-turn"></span></div>
<div class="mt-4 grid grid-cols-3 gap-2" id="live-darts"></div>
<div class="mt-4 flex items-end justify-between border-t border-white/[.06] pt-4"><span class="text-xs text-zinc-500">Součet kola</span><strong class="num text-4xl leading-none" id="live-visit-sum">0</strong></div>
</section>
<section class="panel lg:col-span-7" aria-labelledby="live-log-title">
<p class="eyebrow" id="live-log-title">Poslední kola</p>
<div class="mt-4 flex flex-col gap-2" id="live-visits"></div>
</section>
</div>
</section>
</main>
