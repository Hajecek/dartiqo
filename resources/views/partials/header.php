<?php
$nav = [
    ['Domů', '/'],
    ['Herní módy', '/game-modes'],
    ['Trénink', '/training'],
    ['Kariéra', '/career'],
    ['Online', '/online'],
    ['Statistiky', '/statistics'],
    ['O Dartiqo', '/about'],
];
$current = app()->request()->path();
$aliases = [
    '/herni-mody' => '/game-modes',
    '/funkce' => '/features',
    '/prihlaseni' => '/login',
    '/registrace' => '/register',
];
$current = $aliases[$current] ?? $current;
?>
<header id="site-nav" class="fixed top-4 right-4 left-4 z-50 mx-auto flex max-w-7xl items-center justify-between gap-4 rounded-2xl border border-line bg-canvas/80 px-5 py-3 shadow-[0_10px_35px_#0002] backdrop-blur-3xl [&.scrolled]:top-2 [&.scrolled]:py-[9px] home:absolute home:top-[60px] home:right-[calc(var(--frame-width)+40px)] home:left-[calc(var(--frame-width)+40px)] home:max-w-none home:border-0 home:bg-transparent home:px-0 home:py-2 home:text-white home:shadow-none home:backdrop-blur-none max-md:home:top-[29px] max-md:home:right-[calc(var(--frame-width)+18px)] max-md:home:left-[calc(var(--frame-width)+18px)]">
<a class="flex items-center gap-2 font-display text-2xl font-bold tracking-tighter [&_img]:size-9 [&_img]:rounded-lg max-md:home:text-[22px] max-md:home:[&_img]:size-[30px]" href="<?= e(url('/')) ?>"><img src="<?= e(asset('images/logo.png')) ?>" alt="">dartiqo<span class="text-accent">.</span></a>
<nav class="hidden items-center gap-5 text-[10px] font-medium xl:flex [&_a]:text-muted [&_a]:hover:text-accent [&_a.active]:text-accent home:[&_a]:text-[#bbb] home:[&_a.active]:text-accent">
<?php foreach ($nav as [$label, $href]): ?>
<a href="<?= e(url($href)) ?>" class="<?= $current === $href ? 'active' : '' ?>"><?= e($label) ?></a>
<?php endforeach; ?>
</nav>
<div class="flex items-center gap-3">
<button class="flex size-9 items-center justify-center rounded-full border border-line bg-transparent text-ink home:border-white/15 home:text-[#eee] max-md:home:size-8 controls:border-line! controls:text-ink!" data-theme aria-label="Přepnout světlý a tmavý režim">◐</button>
<a class="text-[11px] hidden sm:block" href="<?= e(url('/login')) ?>">Přihlásit se</a>
<a class="inline-flex items-center justify-center gap-5 rounded-full bg-accent px-6 py-3.5 text-xs font-bold text-black shadow-none transition-all duration-200 hover:-translate-y-[3px] hover:shadow-[0_8px_30px_#faff0020] max-sm:hidden" href="<?= e(url('/register')) ?>">Začít hrát ↗</a>
<button class="flex size-9 items-center justify-center rounded-full border border-line bg-transparent text-ink home:border-white/15 home:text-[#eee] max-md:home:size-8 controls:border-line! controls:text-ink! xl:hidden" id="menu-toggle" aria-expanded="false" aria-label="Otevřít menu">☰</button>
</div>
</header>
