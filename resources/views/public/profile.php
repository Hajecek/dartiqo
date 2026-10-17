<main class="mx-auto w-full max-w-6xl max-w-3xl">
<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Účet</p>
<h1>Profil</h1>
</div>
<a class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-zinc-100 [&_svg]:size-4 [&_b]:rounded-full [&_b]:bg-accent [&_b]:px-1.5 [&_b]:text-[10px] [&_b]:font-bold [&_b]:text-black" href="<?= e(url('/app/settings')) ?>">Nastavení</a>
</header>

<section class="mb-6 rounded-3xl border border-white/10 bg-zinc-900 p-5">
<div class="flex items-center gap-4 [&_strong]:block [&_span]:text-sm [&_span]:text-zinc-400">
<span class="grid size-14 shrink-0 place-items-center rounded-full bg-accent text-lg font-bold text-black" data-player-avatar>A</span>
<div>
<strong data-player-full>Alex Novák</strong>
<span>Expert · <span data-player-user>@alex180</span></span>
</div>
<div class="ml-auto grid size-14 place-items-center rounded-2xl bg-accent text-center text-black [&_small]:text-[9px] [&_b]:font-display [&_b]:text-2xl [&_b]:leading-none"><small>LVL</small><b>12</b></div>
</div>
<div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10 [&_i]:block [&_i]:h-full [&_i]:bg-accent"><i style="width:64%"></i></div>
<p>640 / 1 000 XP do levelu 13</p>
<div class="mt-4 grid grid-cols-3 gap-3 text-center [&_b]:block [&_b]:font-display [&_b]:text-xl [&_span]:text-[10px] [&_span]:uppercase [&_span]:text-zinc-500">
<div><b>56.8</b><span>průměr</span></div>
<div><b>57 %</b><span>výher</span></div>
<div><b>112</b><span>zápasů</span></div>
</div>
</section>

<form class="rounded-3xl border border-line bg-surface p-6 md:p-8 border-white/10! bg-zinc-900! text-zinc-100!" id="profile-form">
<h2>Údaje hráče</h2>
<p>Změny zůstanou v tomhle prohlížeči, jen pro tuhle ukázku.</p>
<div class="relative mx-auto size-32 touch-none overflow-hidden rounded-full bg-surface-2 [&_img]:pointer-events-none [&_img]:size-full [&_img]:object-cover [&_img]:select-none [&_span]:flex [&_span]:h-full [&_span]:items-center [&_span]:justify-center [&_span]:text-5xl" id="profile-avatar"><span>A</span></div>
<label class="flex w-full items-center justify-center gap-3 rounded-xl border border-line bg-surface px-3 py-3 text-xs font-semibold text-ink hover:border-accent mt-4 cursor-pointer">Změnit fotografii<input class="hidden" type="file" accept="image/*" id="profile-photo"></label>
<label for="profile-first">Jméno</label>
<input id="profile-first" type="text" value="Alex" required>
<label for="profile-last">Příjmení</label>
<input id="profile-last" type="text" value="Novák" required>
<label for="profile-user">Uživatelské jméno</label>
<input id="profile-user" type="text" value="alex180" required pattern="[a-zA-Z0-9_]{3,24}">
<button class="inline-flex items-center justify-center gap-5 rounded-full bg-accent px-6 py-3.5 text-xs font-bold text-black shadow-none transition-all duration-200 hover:-translate-y-[3px] hover:shadow-[0_8px_30px_#faff0020] mt-7" type="submit">Uložit změny</button>
</form>
</main>
