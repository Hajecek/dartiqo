<?php
require_once __DIR__ . '/../partials/ui.php';

/** @var array<string, mixed> $friend */
/** @var array<string, mixed> $record */
$user = $friend['user'];
$name = trim((string) $user['first'] . ' ' . (string) $user['last']);
if ($name === '') {
    $name = (string) $user['username'];
}
$letter = mb_strtoupper(mb_substr($name, 0, 1));
$photo = (string) ($user['photo'] ?? '');
$career = $record['career'];
$mutual = $record['mutual'];
$together = (int) $mutual['wins'] + (int) $mutual['losses'] + (int) $mutual['draws'];
$avg = static function (mixed $value): string {
    return is_numeric($value) ? number_format((float) $value, 1, '.', '') : '–';
};
$gamesLabel = static function (int $count): string {
    $mod10 = $count % 10;
    $mod100 = $count % 100;
    if ($mod10 === 1 && $mod100 !== 11) {
        return $count . ' zápas';
    }
    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
        return $count . ' zápasy';
    }

    return $count . ' zápasů';
};
$winRate = (int) $career['played'] > 0 ? (int) round((int) $career['wins'] / (int) $career['played'] * 100) : null;
$csrf = auth()->csrfToken();
$mark = static function (string $result): array {
    return match ($result) {
        'win' => ['V', 'bg-emerald-400/15 text-emerald-300'],
        'loss' => ['P', 'bg-rose-400/15 text-rose-300'],
        default => ['R', 'bg-white/10 text-zinc-300'],
    };
};
?>
<main class="mx-auto w-full max-w-3xl" data-narrow>
<a class="mb-6 inline-flex items-center gap-2 text-sm text-zinc-400 hover:text-white" href="<?= e(url('/app/friends?folder=friends')) ?>"><?= dartiqo_i('back', 'size-4') ?>Kamarádi</a>

<?php if (is_string($flash ?? null) && $flash !== ''): ?>
<p class="mb-4 rounded-2xl bg-accent/10 px-4 py-3 text-sm text-accent" role="status"><?= e($flash) ?></p>
<?php endif; ?>

<header class="mb-8 flex items-center gap-4">
<?php if ($photo !== ''): ?>
<span class="grid size-16 shrink-0 overflow-hidden rounded-full bg-white/10"><img class="size-full object-cover" src="<?= e(url($photo)) ?>" alt=""></span>
<?php else: ?>
<span class="grid size-16 shrink-0 place-items-center rounded-full bg-accent text-xl font-bold text-black"><?= e($letter) ?></span>
<?php endif; ?>
<div class="min-w-0">
<p class="eyebrow">Kamarád</p>
<h1 class="mt-1 truncate"><?= e($name) ?></h1>
<p class="mt-1 text-sm text-zinc-500">@<?= e((string) $user['username']) ?></p>
</div>
</header>

<div class="grid gap-4 sm:grid-cols-2">
<section class="panel" aria-labelledby="career-title">
<h2 id="career-title">Jeho hra</h2>
<p class="mt-1 text-xs text-zinc-500">Z zápasů, které nahrál na svůj účet.</p>
<dl class="mt-5 grid grid-cols-2 gap-4">
<div><dt class="text-[10px] font-semibold tracking-wide text-zinc-500 uppercase">Průměr</dt><dd class="num mt-1 text-3xl leading-none"><?= e($avg($career['average'])) ?></dd></div>
<div><dt class="text-[10px] font-semibold tracking-wide text-zinc-500 uppercase">Zápasy</dt><dd class="num mt-1 text-3xl leading-none"><?= (int) $career['played'] ?></dd></div>
<div><dt class="text-[10px] font-semibold tracking-wide text-zinc-500 uppercase">Výhry</dt><dd class="num mt-1 text-3xl leading-none"><?= (int) $career['wins'] ?></dd></div>
<div><dt class="text-[10px] font-semibold tracking-wide text-zinc-500 uppercase">Úspěšnost</dt><dd class="num mt-1 text-3xl leading-none"><?= $winRate === null ? '–' : $winRate . '%' ?></dd></div>
</dl>
</section>

<section class="panel" aria-labelledby="mutual-title">
<h2 id="mutual-title">Vzájemně</h2>
<p class="mt-1 text-xs text-zinc-500"><?= e((string) $record['standing']) ?></p>
<?php if ($together === 0): ?>
<p class="mt-5 text-sm text-zinc-400">Až spolu odehrajete zápas a nahraje se, objeví se tady bilance i jednotlivé hry.</p>
<?php else: ?>
<p class="num mt-5 text-5xl leading-none"><?= (int) $mutual['wins'] ?> <span class="text-zinc-600">:</span> <?= (int) $mutual['losses'] ?></p>
<p class="mt-2 text-xs text-zinc-500"><?= e($gamesLabel($together)) ?><?php if ((int) $mutual['draws'] > 0): ?> · <?= (int) $mutual['draws'] ?> <?= (int) $mutual['draws'] === 1 ? 'remíza' : ((int) $mutual['draws'] < 5 ? 'remízy' : 'remíz') ?><?php endif; ?></p>
<dl class="mt-5 grid grid-cols-2 gap-4">
<div><dt class="text-[10px] font-semibold tracking-wide text-zinc-500 uppercase">Tvůj průměr</dt><dd class="num mt-1 text-2xl leading-none"><?= e($avg($record['myAverage'])) ?></dd></div>
<div><dt class="text-[10px] font-semibold tracking-wide text-zinc-500 uppercase">Průměr <?= e($user['first'] !== '' ? (string) $user['first'] : (string) $user['username']) ?></dt><dd class="num mt-1 text-2xl leading-none"><?= e($avg($record['theirAverage'])) ?></dd></div>
</dl>
<?php endif; ?>
</section>
</div>

<section class="panel mt-4" aria-labelledby="games-title">
<h2 id="games-title">Vzájemné zápasy</h2>
<?php if ($record['games'] === []): ?>
<p class="mt-4 text-sm text-zinc-500">Zatím tu žádný není.</p>
<?php else: ?>
<ul class="mt-4 flex flex-col gap-2">
<?php foreach ($record['games'] as $game): ?>
<?php $style = $mark((string) $game['result']); ?>
<li>
<?php if (!empty($game['mine'])): ?>
<a class="flex items-center gap-3 rounded-2xl bg-white/[.035] px-3 py-3 ring-1 ring-white/[.06] ring-inset hover:bg-white/[.06]" href="<?= e(url('/app/games/' . $game['id'])) ?>">
<?php else: ?>
<div class="flex items-center gap-3 rounded-2xl bg-white/[.035] px-3 py-3 ring-1 ring-white/[.06] ring-inset">
<?php endif; ?>
<span class="grid size-11 shrink-0 place-items-center rounded-xl text-sm font-bold <?= e($style[1]) ?>"><?= e($style[0]) ?></span>
<span class="min-w-0 flex-1">
<strong class="block truncate text-sm"><?= e((string) $game['title']) ?></strong>
<small class="mt-0.5 block truncate text-xs text-zinc-500"><?= e((string) $game['mode']) ?> · <?= e((string) $game['when']) ?><?php if (is_numeric($game['myAverage']) || is_numeric($game['theirAverage'])): ?> · <?= e($avg($game['myAverage'])) ?> / <?= e($avg($game['theirAverage'])) ?><?php endif; ?></small>
</span>
<span class="shrink-0 text-right">
<span class="num block text-xl leading-none"><?= e((string) $game['score']) ?></span>
<small class="mt-1 block text-[10px] font-semibold tracking-wide text-zinc-500 uppercase"><?= e((string) $game['label']) ?></small>
</span>
<?php if (!empty($game['mine'])): ?>
</a>
<?php else: ?>
</div>
<?php endif; ?>
</li>
<?php endforeach; ?>
</ul>
<p class="mt-3 text-xs text-zinc-500">Hru, kterou jsi nahrál ty, jde otevřít. Zápas z jeho telefonu je tady jen jako výsledek.</p>
<?php endif; ?>
</section>

<form class="mt-6" method="post" action="<?= e(url('/app/friends/' . (int) $friend['id'] . '/delete')) ?>">
<input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
<button class="pill text-rose-300!" type="submit">Odebrat z kamarádů</button>
</form>
</main>
