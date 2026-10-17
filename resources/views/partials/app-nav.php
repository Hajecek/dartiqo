<?php
$path = app()->request()->path();
$tabs = [
    ['home', 'Přehled', '/app', 'M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z'],
    ['stats', 'Statistiky', '/app/statistics', 'M4 19V9 M10 19V5 M16 19v-6 M22 19V8'],
    ['games', 'Moje hry', '/app/games', 'M8 6h12M8 12h12M8 18h8M4 6h.01M4 12h.01M4 18h.01'],
    ['profile', 'Profil', '/app/profile', 'M12 12a3.6 3.6 0 1 0 0-7.2 3.6 3.6 0 0 0 0 7.2Z M5.5 20.2a6.5 6.5 0 0 1 13 0'],
];
$icon = static function (string $d): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' . $d . '"/></svg>';
};
$activeFor = static function (string $id) use ($path): bool {
    return match ($id) {
        'profile' => $path === '/app/profile',
        'stats' => $path === '/app/statistics',
        'games' => $path === '/app/games' || str_starts_with($path, '/app/games/'),
        'home' => $path === '/app',
        default => false,
    };
};
?>
<aside id="app-rail" class="fixed z-40 hidden flex-col rounded-3xl border border-white/10 bg-zinc-900/95 shadow-[0_18px_50px_rgb(0_0_0/0.35)] backdrop-blur-xl lg:flex" aria-label="Aplikace">
<div class="rail-clip">
<div class="rail-head">
<a class="rail-row min-w-0 font-display text-2xl font-bold tracking-tighter [&_img]:size-9 [&_img]:shrink-0 [&_img]:rounded-lg" href="<?= e(url('/app')) ?>" title="Dartiqo"><img src="<?= e(asset('images/logo.png')) ?>" alt=""><span class="rail-label">dartiqo</span></a>
</div>
<nav class="mt-6 flex flex-1 flex-col gap-1" aria-label="Sekce">
<?php foreach ($tabs as [$id, $label, $href, $d]): ?>
<a class="rail-row rounded-2xl px-3 py-2.5 text-sm text-zinc-400 hover:bg-white/5 hover:text-zinc-100 [&.active]:bg-accent [&.active]:font-semibold [&.active]:text-black [&_svg]:size-5 [&_svg]:shrink-0<?= $activeFor($id) ? ' active' : '' ?>" data-tab="<?= e($id) ?>" href="<?= e(url($href)) ?>" title="<?= e($label) ?>"><?= $icon($d) ?><span class="rail-label"><?= e($label) ?></span></a>
<?php endforeach; ?>
</nav>
<div class="mt-auto border-t border-white/10 pt-4">
<a class="rail-row rounded-2xl px-1 py-2 [&_strong]:block [&_strong]:text-sm [&_small]:text-xs [&_small]:text-zinc-500" href="<?= e(url('/app/profile')) ?>" title="Profil">
<span class="grid size-10 shrink-0 place-items-center rounded-full bg-accent text-sm font-bold text-black" data-player-avatar>A</span>
<span class="rail-label"><strong data-player-full>Alex Novák</strong><small data-player-user>@alex180</small></span>
</a>
<div class="rail-foot mt-2 flex gap-1 px-1 text-xs text-zinc-400 [&_a]:text-zinc-400 [&_a]:hover:text-white [&_button]:text-zinc-400 [&_button]:hover:text-white [&_svg]:size-4 [&_svg]:shrink-0">
<a class="rail-row rounded-xl px-2 py-2 hover:bg-white/5" href="<?= e(url('/app/settings')) ?>" title="Nastavení"><?= $icon('M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Z M12 3.5v2 M12 18.5v2 M4.8 6.2l1.5 1.5 M17.7 16.3l1.5 1.5 M3.5 12h2 M18.5 12h2 M4.8 17.8l1.5-1.5 M17.7 7.7l1.5-1.5') ?><span class="rail-label">Nastavení</span></a>
<button class="rail-row rounded-xl px-2 py-2 hover:bg-white/5" type="button" data-logout title="Odhlásit"><?= $icon('M10 7V5H5v14h5v-2 M10 12h9 M16 8l4 4-4 4') ?><span class="rail-label">Odhlásit</span></button>
</div>
</div>
</div>
<button class="rail-pin" type="button" id="rail-toggle" aria-expanded="true" aria-controls="app-rail" aria-label="Sbalit menu">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6 9 12l6 6"/></svg>
</button>
</aside>
<nav class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-4 border-t border-white/10 bg-zinc-950/95 px-1 py-1 backdrop-blur-xl lg:hidden [&_a]:flex [&_a]:flex-col [&_a]:items-center [&_a]:gap-1 [&_a]:py-1 [&_a]:text-[10px] [&_a]:text-zinc-500 [&_a.active]:text-accent [&_svg]:size-5" aria-label="Hlavní sekce">
<?php foreach ($tabs as [$id, $label, $href, $d]): ?>
<a class="<?= $activeFor($id) ? 'active' : '' ?>" data-tab="<?= e($id) ?>" href="<?= e(url($href)) ?>"><?= $icon($d) ?><span><?= e($label) ?></span></a>
<?php endforeach; ?>
</nav>
