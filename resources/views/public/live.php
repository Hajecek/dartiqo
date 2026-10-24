<?php
require_once __DIR__ . '/../partials/ui.php';
?>
<main class="match-stage flex min-h-svh flex-col gap-3 p-3 sm:p-5 lg:h-svh lg:gap-4 lg:overflow-hidden 2xl:p-8" id="live-page">
<header class="flex shrink-0 items-center gap-3">
<a class="match-glass grid size-11 shrink-0 place-items-center rounded-[14px] text-white hover:bg-white/15 2xl:size-14 [&_svg]:size-5 2xl:[&_svg]:size-6" href="<?= e(url('/app')) ?>" title="Domů" aria-label="Domů"><?= dartiqo_i('home') ?></a>
<div class="min-w-0 flex-1">
<div class="flex min-w-0 items-center gap-2 text-xs font-semibold text-white/55 2xl:text-sm"><span id="live-badge" class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-bold tracking-wider text-white/60 uppercase">Připojuji…</span><span class="truncate" id="live-meta"></span></div>
<h1 class="mt-0.5 truncate text-xl! leading-tight! text-white md:text-2xl! 2xl:text-4xl!" id="live-title">Živý zápas</h1>
</div>
<button class="match-glass inline-flex h-11 shrink-0 items-center gap-2 rounded-[14px] px-3.5 text-sm font-semibold whitespace-nowrap text-white hover:bg-white/15 2xl:h-14 2xl:px-5 2xl:text-base [&_svg]:size-5" type="button" data-live-fullscreen title="Celá obrazovka"><?= dartiqo_i('expand') ?><span class="max-sm:hidden" data-live-fullscreen-label>Celá obrazovka</span></button>
</header>

<section class="match-glass flex flex-1 flex-col items-center justify-center gap-4 rounded-[22px] px-6 py-16 text-center" id="live-empty">
<span class="grid size-16 place-items-center rounded-full bg-[#fefc03]/15 text-[#fefc03] 2xl:size-24 [&_svg]:size-7 2xl:[&_svg]:size-11"><?= dartiqo_i('target') ?></span>
<h2 class="text-white 2xl:text-3xl!">Čekám na zápas z mobilu</h2>
<span class="max-w-md text-sm leading-relaxed text-white/60 2xl:max-w-xl 2xl:text-lg">V aplikaci Dartiqo se přihlas ke stejnému účtu (Profil → Nastavení → Dartiqo účet) a spusť zápas. Skóre se tu objeví samo, stránku nech otevřenou.</span>
</section>

<section class="grid flex-1 gap-3 lg:min-h-0 lg:grid-cols-[minmax(0,1fr)_clamp(300px,24vw,480px)] lg:gap-4" id="live-board" hidden>
<div class="flex min-w-0 flex-col gap-2 lg:min-h-0 2xl:gap-3">
<div class="shrink-0" id="live-progress"></div>
<div class="relative flex min-h-[220px] flex-1 flex-col [container-type:size] max-lg:min-h-[32svh]">
<div class="grid flex-1 auto-rows-fr overflow-hidden rounded-[22px] 2xl:rounded-[32px]" id="live-players"></div>
<i class="absolute bottom-0 h-2.5 w-[18px] -translate-x-1/2 bg-[#0e1a14] transition-[left] duration-500 ease-[cubic-bezier(.65,0,.35,1)] [clip-path:polygon(0_100%,50%_0,100%_100%)] 2xl:h-4 2xl:w-7" id="live-notch" aria-hidden="true" hidden></i>
</div>
<div class="flex shrink-0 flex-col gap-2 2xl:gap-3" id="live-strip"></div>
</div>
<section class="match-glass flex flex-col rounded-[22px] p-4 lg:min-h-0 2xl:rounded-[32px] 2xl:p-5" aria-labelledby="live-log-title">
<div class="flex shrink-0 items-baseline justify-between gap-3"><h2 class="text-xs! font-bold tracking-wider text-white/55 uppercase 2xl:text-sm!" id="live-log-title">Historie hodů</h2><span class="text-[11px] font-semibold text-white/40 2xl:text-xs">nejnovější nahoře</span></div>
<div class="shrink-0" id="live-routes"></div>
<div class="mt-2 grid shrink-0 grid-cols-[minmax(0,1.1fr)_minmax(0,1.6fr)_auto] gap-x-2 px-1 text-[10px] font-bold tracking-wider text-white/35 uppercase 2xl:text-xs" aria-hidden="true"><span>Hráč</span><span>Šipky</span><span class="text-right">Body</span></div>
<div class="mt-1 flex flex-col gap-0.5 lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:[scrollbar-width:none]" id="live-visits"></div>
</section>
</section>
</main>
