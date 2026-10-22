<?php
require_once __DIR__ . '/../partials/ui.php';

$levelRing = 2 * M_PI * 44;
$stats = [
    ['Průměr', '56.8', 'celkově'],
    ['Výhry', '57 %', '64 z 112'],
    ['Zápasy', '112', 'odehráno'],
    ['Maximum 180', '22', 'celkem'],
];
$badges = [
    ['trophy', 'První výhra', 'text-accent bg-accent/10 ring-accent/25'],
    ['bolt', 'Maximum 180', 'text-orange-300 bg-orange-400/10 ring-orange-400/25'],
    ['target', 'Zavření 100+', 'text-emerald-300 bg-emerald-400/10 ring-emerald-400/25'],
    ['flame', '7 dní v řadě', 'text-zinc-500 bg-white/[.04] ring-white/10'],
];
?>
<main class="@container/dash mx-auto w-full max-w-5xl" data-narrow>
<header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="eyebrow">Účet</p>
<h1 class="mt-2">Profil</h1>
</div>
<a class="pill" href="<?= e(url('/app/settings')) ?>"><?= dartiqo_i('gear') ?>Nastavení</a>
</header>

<section class="panel p-0!">
<div class="relative h-36 overflow-hidden sm:h-44" aria-hidden="true">
<div class="absolute inset-0 bg-[radial-gradient(80%_120%_at_85%_0%,#faff0033,transparent_60%),radial-gradient(60%_100%_at_10%_100%,#34d39922,transparent_60%)]"></div>
<div class="absolute inset-0 bg-[linear-gradient(#ffffff0d_1px,transparent_1px),linear-gradient(90deg,#ffffff0d_1px,transparent_1px)] [mask-image:linear-gradient(to_bottom,#000,transparent)] bg-[size:32px_32px]"></div>
<svg class="absolute -top-16 -right-10 size-72 text-accent/20" viewBox="0 0 100 100" fill="none" stroke="currentColor"><circle cx="50" cy="50" r="48"/><circle cx="50" cy="50" r="36"/><circle cx="50" cy="50" r="24"/><circle cx="50" cy="50" r="12"/><circle cx="50" cy="50" r="3" fill="currentColor"/></svg>
</div>
<div class="relative flex flex-col gap-5 px-5 pb-6 sm:flex-row sm:items-end sm:px-8 sm:pb-8">
<span class="-mt-14 grid size-28 shrink-0 place-items-center overflow-hidden rounded-[28px] bg-accent text-4xl font-bold text-black shadow-[0_18px_40px_-14px_#faff00aa] ring-[6px] ring-zinc-900 [&_img]:size-full [&_img]:object-cover" data-player-avatar>A</span>
<div class="min-w-0 flex-1">
<strong class="block truncate text-2xl font-semibold tracking-tight" data-player-full>Alex Novák</strong>
<span class="mt-1.5 flex flex-wrap items-center gap-2 text-sm text-zinc-400"><span data-player-user>@alex180</span><span class="size-1 rounded-full bg-zinc-600"></span><span class="chip bg-accent/10 text-accent ring-1 ring-accent/25 ring-inset">Expert</span></span>
</div>
<div class="flex items-center gap-4 rounded-2xl bg-white/[.035] py-2.5 pr-5 pl-2.5 ring-1 ring-white/[.06] ring-inset">
<span class="relative grid size-16 place-items-center">
<svg class="absolute inset-0 -rotate-90" viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="44" fill="none" stroke="rgb(255 255 255 / .08)" stroke-width="8"/><circle cx="50" cy="50" r="44" fill="none" stroke="#faff00" stroke-width="8" stroke-linecap="round" stroke-dasharray="<?= e((string) round($levelRing * .64, 2)) ?> <?= e((string) round($levelRing, 2)) ?>"/></svg>
<b class="num text-xl">12</b>
</span>
<span><small class="eyebrow block">Level</small><span class="num mt-1 block text-sm">640 <span class="text-zinc-500">/ 1 000 XP</span></span></span>
</div>
</div>
<div class="grid grid-cols-2 gap-px border-t border-white/[.06] bg-white/[.06] @2xl/dash:grid-cols-4">
<?php foreach ($stats as [$label, $value, $note]): ?>
<div class="bg-zinc-900/95 px-5 py-4 sm:px-8"><span class="eyebrow tracking-[.12em]"><?= e($label) ?></span><strong class="num mt-2 block text-2xl leading-none"><?= e($value) ?></strong><small class="mt-1 block text-[11px] text-zinc-500"><?= e($note) ?></small></div>
<?php endforeach; ?>
</div>
</section>

<div class="mt-4 grid items-start gap-4 @3xl/dash:grid-cols-[minmax(0,.8fr)_minmax(0,1.2fr)]">
<div class="flex flex-col gap-4">
<section class="panel text-center" aria-labelledby="photo-title">
<h2 class="text-left" id="photo-title">Profilová fotka</h2>
<p class="mt-1 text-left text-xs text-zinc-500">Čtvercový obrázek do 5 MB.</p>
<div class="relative mx-auto mt-6 size-32 touch-none overflow-hidden rounded-[32px] bg-white/[.04] ring-1 ring-white/10 ring-inset [&_img]:pointer-events-none [&_img]:size-full [&_img]:object-cover [&_img]:select-none [&_span]:flex [&_span]:h-full [&_span]:items-center [&_span]:justify-center [&_span]:text-5xl [&_span]:font-bold [&_span]:text-accent" id="profile-avatar"><span>A</span></div>
<label class="btn-ghost mt-5 mb-0! w-full cursor-pointer text-zinc-100!"><?= dartiqo_i('camera') ?>Změnit fotografii<input class="hidden" type="file" accept="image/*" id="profile-photo"></label>
</section>
<section class="panel" aria-labelledby="badges-title">
<h2 id="badges-title">Odznaky</h2>
<div class="mt-4 grid grid-cols-2 gap-2">
<?php foreach ($badges as [$icon, $label, $tone]): ?>
<div class="tile flex items-center gap-3 p-3"><span class="grid size-9 shrink-0 place-items-center rounded-xl ring-1 ring-inset <?= e($tone) ?>"><?= dartiqo_i($icon, 'size-4') ?></span><span class="text-xs font-medium text-zinc-300"><?= e($label) ?></span></div>
<?php endforeach; ?>
</div>
</section>
</div>

<form class="panel" id="profile-form">
<h2>Údaje hráče</h2>
<p class="mt-1 text-xs text-zinc-500">Změny zůstanou v tomhle prohlížeči, jen pro tuhle ukázku.</p>
<div class="mt-6 grid gap-4 sm:grid-cols-2">
<div>
<label for="profile-first">Jméno</label>
<input id="profile-first" type="text" value="Alex" required autocomplete="given-name">
</div>
<div>
<label for="profile-last">Příjmení</label>
<input id="profile-last" type="text" value="Novák" required autocomplete="family-name">
</div>
<div class="sm:col-span-2">
<label for="profile-user">Uživatelské jméno</label>
<div class="relative">
<span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-sm text-zinc-500">@</span>
<input class="pl-9!" id="profile-user" type="text" value="alex180" required pattern="[a-zA-Z0-9_]{3,24}" autocomplete="username">
</div>
<small class="mt-2 block text-[11px] text-zinc-500">3–24 znaků: písmena, čísla a podtržítko.</small>
</div>
</div>
<div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-white/[.06] pt-5">
<a class="text-xs text-zinc-500 hover:text-zinc-300" href="<?= e(url('/app/settings')) ?>">Další nastavení účtu</a>
<button class="btn-primary" type="submit"><?= dartiqo_i('check') ?>Uložit změny</button>
</div>
</form>
</div>
</main>
