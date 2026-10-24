<?php
require_once __DIR__ . '/../partials/ui.php';

$rowIcon = static fn (string $icon, string $tone = 'bg-white/[.05] text-zinc-300 ring-white/10'): string => '<span class="grid size-10 shrink-0 place-items-center rounded-xl ring-1 ring-inset ' . $tone . '">' . dartiqo_i($icon, 'size-[18px]') . '</span>';
$account = auth()->user();
$accountPublic = is_array($account) && (string) ($account['visibility'] ?? 'public') !== 'private';
$visibilityCopy = $accountPublic
    ? 'Kamarádi uvidí tvoje statistiky, průměry i vzájemné zápasy.'
    : 'Kamarádi uvidí jen jméno a fotku. Průměry, zápasy ani vzájemná bilance se jim neukážou.';
?>
<main class="mx-auto w-full max-w-3xl" data-narrow>
<header class="mb-8">
<p class="eyebrow">Účet</p>
<h1 class="mt-2">Nastavení</h1>
<p class="mt-1 text-sm text-zinc-400">Soukromí, vzhled a upozornění na jednom místě.</p>
</header>

<form class="flex flex-col gap-4" id="profile-form">
<section class="panel" aria-labelledby="privacy-title">
<p class="eyebrow" id="privacy-title">Soukromí</p>
<div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
<?= $rowIcon('lock') ?>
<div class="min-w-0 flex-1">
<strong class="block text-sm font-semibold">Soukromý, nebo veřejný účet</strong>
<p class="text-xs text-zinc-500" id="visibility-copy"><?= e($visibilityCopy) ?></p>
</div>
<div class="seg shrink-0" id="account-visibility" role="group" aria-label="Viditelnost účtu" data-value="<?= $accountPublic ? 'public' : 'private' ?>">
<button type="button" data-visibility="private" class="<?= $accountPublic ? '' : 'active' ?>" aria-pressed="<?= $accountPublic ? 'false' : 'true' ?>">Soukromý</button>
<button type="button" data-visibility="public" class="<?= $accountPublic ? 'active' : '' ?>" aria-pressed="<?= $accountPublic ? 'true' : 'false' ?>">Veřejný</button>
</div>
</div>
</section>

<section class="panel" aria-labelledby="look-title">
<p class="eyebrow">Vzhled</p>
<h2 class="sr-only" id="look-title">Vzhled</h2>
<div class="mt-4 flex items-center gap-4">
<?= $rowIcon('moon', 'bg-accent/10 text-accent ring-accent/25') ?>
<div class="min-w-0 flex-1">
<strong class="block text-sm font-semibold">Světlý / tmavý režim</strong>
<p class="text-xs text-zinc-500">Platí pro aplikaci i marketingové stránky a zapamatuje se v tomhle prohlížeči.</p>
</div>
<button type="button" data-theme class="pill shrink-0">Přepnout</button>
</div>
</section>

<section class="panel" aria-labelledby="notify-title">
<p class="eyebrow" id="notify-title">Upozornění</p>
<div class="mt-2 divide-y divide-white/[.06]">
<label class="m-0! flex cursor-pointer items-center gap-4 py-4 text-zinc-100!" for="notify-demo">
<?= $rowIcon('bell') ?>
<span class="min-w-0 flex-1"><strong class="block text-sm font-semibold">Připomínky tréninku</strong><small class="block text-xs font-normal text-zinc-500">Krátká připomínka, když ti hrozí ztráta série.</small></span>
<input class="switch" type="checkbox" id="notify-demo" checked>
</label>
<label class="m-0! flex cursor-pointer items-center gap-4 pt-4 pb-1 text-zinc-100!" for="news-demo">
<?= $rowIcon('mail') ?>
<span class="min-w-0 flex-1"><strong class="block text-sm font-semibold">Novinky z Dartiqo</strong><small class="block text-xs font-normal text-zinc-500">Nové režimy, turnaje a tipy na trénink.</small></span>
<input class="switch" type="checkbox" id="news-demo">
</label>
</div>
</section>

<section class="panel" aria-labelledby="accounts-title">
<p class="eyebrow" id="accounts-title">Propojené účty</p>
<div class="mt-2 divide-y divide-white/[.06]">
<?php foreach ([['Apple', 'M15.5 3.5c-.9.1-2 .7-2.6 1.5-.6.7-1 1.7-.9 2.6 1 .1 2-.5 2.6-1.3.6-.8 1-1.8.9-2.8Z M18.6 16.3c-.5 1.1-.7 1.6-1.3 2.6-.9 1.4-2.1 3-3.6 3-1.3 0-1.7-.8-3.4-.8-1.8 0-2.2.8-3.5.8-1.5 0-2.6-1.5-3.5-2.9C1 15.6.7 11.2 2.3 8.9c1.1-1.6 2.9-2.6 4.6-2.6 1.7 0 2.8.9 4.2.9 1.4 0 2.2-.9 4.2-.9 1.5 0 3.1.8 4.2 2.2-3.7 2-3.1 7.3-.9 7.8Z'], ['Facebook', 'M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v8h4v-8h3l1-4h-4V8Z']] as [$name, $d]): ?>
<div class="flex items-center gap-4 py-4 last:pb-1">
<span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white/[.05] text-zinc-200 ring-1 ring-white/10 ring-inset"><?= dartiqo_icon($d, 'size-[18px]') ?></span>
<span class="min-w-0 flex-1"><strong class="block text-sm font-semibold"><?= e($name) ?></strong><small class="block text-xs text-zinc-500">Nepropojeno</small></span>
<button type="button" class="pill shrink-0" data-social="<?= e($name) ?>"><?= dartiqo_i('link') ?>Propojit</button>
</div>
<?php endforeach; ?>
</div>
</section>

<section class="panel" aria-labelledby="security-title">
<p class="eyebrow" id="security-title">Zabezpečení</p>
<div class="mt-2 divide-y divide-white/[.06]">
<a class="group flex items-center gap-4 py-4" href="<?= e(url('/reset-password')) ?>">
<?= $rowIcon('lock') ?>
<span class="min-w-0 flex-1"><strong class="block text-sm font-semibold">Změnit heslo</strong><small class="block text-xs text-zinc-500">Doporučujeme alespoň 12 znaků.</small></span>
<?= dartiqo_i('chevron', 'size-4 text-zinc-600 transition group-hover:translate-x-0.5 group-hover:text-zinc-300') ?>
</a>
<button class="group flex w-full items-center gap-4 pt-4 pb-1 text-left" type="button" data-logout>
<?= $rowIcon('logout', 'bg-rose-400/10 text-rose-300 ring-rose-400/25') ?>
<span class="min-w-0 flex-1"><strong class="block text-sm font-semibold text-rose-300">Odhlásit se</strong><small class="block text-xs text-zinc-500">Ukončí ukázkovou relaci v tomhle prohlížeči.</small></span>
</button>
</div>
</section>

<div class="sticky bottom-24 z-10 flex items-center justify-between gap-3 rounded-full border border-white/[.08] bg-zinc-900/85 py-2 pr-2 pl-5 shadow-[0_20px_40px_-20px_#000] backdrop-blur-xl lg:bottom-6">
<span class="text-xs text-zinc-400">Viditelnost účtu se ukládá hned. Ostatní změny jen pro tuto relaci.</span>
<button class="btn-primary h-10" type="submit"><?= dartiqo_i('check') ?>Uložit změny</button>
</div>
</form>
</main>
