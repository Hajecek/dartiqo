<main class="mx-auto w-full max-w-6xl max-w-3xl">
<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="text-[10px] font-bold uppercase tracking-[.22em] text-muted">Účet</p>
<h1>Nastavení</h1>
</div>
</header>
<form class="rounded-3xl border border-line bg-surface p-6 md:p-8 border-white/10! bg-zinc-900! text-zinc-100!" id="profile-form">
<h2>Vzhled a notifikace</h2>
<p>Přepínač vzhledu mění marketingové stránky. Herní přehled zůstává černý, stejně jako lobby v aplikaci.</p>
<button type="button" data-theme class="inline-flex items-center justify-center gap-5 rounded-full border border-line bg-transparent px-6 py-3.5 text-xs font-bold text-ink shadow-none transition-all duration-200 hover:-translate-y-[3px] hover:shadow-[0_8px_30px_#faff0020] mt-5">Přepnout světlý / tmavý režim</button>
<label class="flex gap-3 items-center"><input type="checkbox" id="notify-demo" checked> Připomínky tréninku</label>
<label class="flex gap-3 items-center"><input type="checkbox" id="news-demo"> Novinky z Dartiqo</label>
<h2 class="mt-8">Propojené účty</h2>
<div class="grid gap-3 mt-4"><div class="grid grid-cols-2 gap-3"><button type="button" class="flex w-full items-center justify-center gap-3 rounded-xl border border-line bg-surface px-3 py-3 text-xs font-semibold text-ink hover:border-accent" data-social="Apple">Apple</button><button type="button" class="flex w-full items-center justify-center gap-3 rounded-xl border border-line bg-surface px-3 py-3 text-xs font-semibold text-ink hover:border-accent" data-social="Facebook">Facebook</button></div></div>
<a href="<?= e(url('/reset-password')) ?>" class="text-xs text-accent block mt-6">Změnit heslo</a>
<button class="inline-flex items-center justify-center gap-5 rounded-full bg-accent px-6 py-3.5 text-xs font-bold text-black shadow-none transition-all duration-200 hover:-translate-y-[3px] hover:shadow-[0_8px_30px_#faff0020] mt-7" type="submit">Uložit změny</button>
</form>
</main>
