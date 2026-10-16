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
<header class="nav">
<a class="brand" href="<?= e(url('/')) ?>"><img src="<?= e(asset('images/logo.png')) ?>" alt="">dartiqo<span class="text-accent">.</span></a>
<nav class="nav-links">
<?php foreach ($nav as [$label, $href]): ?>
<a href="<?= e(url($href)) ?>" class="<?= $current === $href ? 'active' : '' ?>"><?= e($label) ?></a>
<?php endforeach; ?>
</nav>
<div class="flex items-center gap-3">
<button class="icon-btn" data-theme aria-label="Přepnout světlý a tmavý režim">◐</button>
<a class="text-[11px] hidden sm:block" href="<?= e(url('/login')) ?>">Přihlásit se</a>
<a class="btn hidden sm:inline-flex" href="<?= e(url('/register')) ?>">Začít hrát ↗</a>
<button class="icon-btn xl:hidden" id="menu-toggle" aria-expanded="false" aria-label="Otevřít menu">☰</button>
</div>
</header>
