<?php
require_once __DIR__ . '/../partials/ui.php';

/** @var array{friends: list<array<string, mixed>>, incoming: list<array<string, mixed>>, outgoing: list<array<string, mixed>>} $book */
/** @var list<array<string, mixed>> $people */
$folder = ($folder ?? 'friends') === 'requests' ? 'requests' : 'friends';
$query = (string) ($query ?? '');
$flash = $flash ?? null;
$incomingCount = count($book['incoming']);

$avatar = static function (array $user): string {
    $name = trim((string) $user['first'] . ' ' . (string) $user['last']);
    $label = $name !== '' ? $name : (string) $user['username'];
    $letter = mb_strtoupper(mb_substr($label, 0, 1));
    $photo = (string) ($user['photo'] ?? '');
    if ($photo !== '') {
        return '<span class="grid size-11 shrink-0 overflow-hidden rounded-full bg-white/10"><img class="size-full object-cover" src="' . e(url($photo)) . '" alt=""></span>';
    }

    return '<span class="grid size-11 shrink-0 place-items-center rounded-full bg-accent text-sm font-bold text-black">' . e($letter) . '</span>';
};

$person = static function (array $user) use ($avatar): void {
    $name = trim((string) $user['first'] . ' ' . (string) $user['last']);
    if ($name === '') {
        $name = (string) $user['username'];
    }
    echo $avatar($user);
    echo '<span class="min-w-0 flex-1"><strong class="block truncate text-sm">' . e($name) . '</strong>';
    echo '<span class="mt-0.5 block text-xs text-zinc-500">@' . e((string) $user['username']) . '</span></span>';
};

$csrf = auth()->csrfToken();
?>
<main class="mx-auto w-full max-w-3xl" data-narrow>
<header class="mb-8">
<p class="eyebrow">Lidé</p>
<h1 class="mt-2">Kamarádi</h1>
<p class="mt-1 text-sm text-zinc-400">Žádost musí druhý hráč potvrdit. Do té doby mezi kamarády není.</p>
</header>

<nav class="seg mb-6" aria-label="Složky">
<a class="<?= $folder === 'friends' ? 'active' : '' ?>" href="<?= e(url('/app/friends?folder=friends' . ($query !== '' ? '&q=' . rawurlencode($query) : ''))) ?>">Kamarádi</a>
<a class="<?= $folder === 'requests' ? 'active' : '' ?>" href="<?= e(url('/app/friends?folder=requests')) ?>">Žádosti<?php if ($incomingCount > 0): ?><span class="grid h-5 min-w-5 place-items-center rounded-full bg-accent px-1.5 text-[10px] font-bold text-black"><?= $incomingCount ?></span><?php endif; ?></a>
</nav>

<?php if (is_string($flash) && $flash !== ''): ?>
<p class="mb-4 rounded-2xl bg-accent/10 px-4 py-3 text-sm text-accent" role="status"><?= e($flash) ?></p>
<?php endif; ?>

<?php if ($folder === 'friends'): ?>
<form class="panel mb-4" method="get" action="<?= e(url('/app/friends')) ?>">
<input type="hidden" name="folder" value="friends">
<label for="friend-query">Najít hráče</label>
<div class="flex flex-col gap-3 sm:flex-row">
<div class="relative min-w-0 flex-1">
<?= dartiqo_i('search', 'pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-zinc-500') ?>
<input class="h-11! rounded-full! pl-11!" id="friend-query" name="q" type="search" value="<?= e($query) ?>" placeholder="Jméno, username nebo e-mail" autocomplete="off">
</div>
<button class="btn-primary shrink-0" type="submit">Hledat</button>
</div>
</form>

<?php if ($query !== ''): ?>
<section class="panel mb-4" aria-labelledby="search-title">
<h2 id="search-title">Výsledky</h2>
<?php if ($people === []): ?>
<p class="mt-4 text-sm text-zinc-500">Nikdo takový v Dartiqo není.</p>
<?php else: ?>
<ul class="mt-4 flex flex-col gap-3">
<?php foreach ($people as $hit): ?>
<?php $relation = (string) ($hit['relation'] ?? 'none'); ?>
<li class="flex items-center gap-3">
<?php $person($hit); ?>
<?php if ($relation === 'accepted'): ?>
<span class="text-xs font-semibold text-zinc-400">Kamarádi</span>
<?php elseif ($relation === 'outgoing'): ?>
<span class="text-xs font-semibold text-zinc-400">Odesláno</span>
<?php else: ?>
<form method="post" action="<?= e(url('/app/friends')) ?>">
<input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
<input type="hidden" name="userId" value="<?= (int) $hit['id'] ?>">
<input type="hidden" name="q" value="<?= e($query) ?>">
<button class="btn-primary h-9! px-4! text-xs!" type="submit"><?= $relation === 'incoming' ? 'Přijmout' : 'Žádost' ?></button>
</form>
<?php endif; ?>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</section>
<?php endif; ?>

<section class="panel" aria-labelledby="friends-title">
<h2 id="friends-title">Přijatí</h2>
<?php if ($book['friends'] === []): ?>
<p class="mt-4 text-sm text-zinc-500">Zatím tu nikdo není. Najdi hráče a pošli mu žádost.</p>
<?php else: ?>
<ul class="mt-4 flex flex-col gap-2">
<?php foreach ($book['friends'] as $link): ?>
<?php
    $summary = $link['summary'] ?? ['career' => ['played' => 0, 'average' => null], 'mutual' => ['wins' => 0, 'losses' => 0, 'draws' => 0]];
    $mutual = $summary['mutual'];
    $playedTogether = (int) $mutual['wins'] + (int) $mutual['losses'] + (int) $mutual['draws'];
    $career = $summary['career'];
    $friendName = trim((string) $link['user']['first'] . ' ' . (string) $link['user']['last']);
    if ($friendName === '') {
        $friendName = (string) $link['user']['username'];
    }
    $hint = '@' . $link['user']['username'];
    if ($career['average'] !== null) {
        $hint .= ' · průměr ' . number_format((float) $career['average'], 1, '.', '');
    } elseif ((int) $career['played'] > 0) {
        $hint .= ' · ' . (int) $career['played'] . ' zápasů';
    }
?>
<li class="flex items-center gap-2 rounded-2xl py-1">
<a class="flex min-w-0 flex-1 items-center gap-3 rounded-2xl px-1 py-1 hover:bg-white/[.04]" href="<?= e(url('/app/friends/' . (int) $link['id'])) ?>">
<?php $avatar($link['user']); ?>
<span class="min-w-0 flex-1">
<strong class="block truncate text-sm"><?= e($friendName) ?></strong>
<span class="mt-0.5 block truncate text-xs text-zinc-500"><?= e($hint) ?></span>
</span>
<?php if ($playedTogether > 0): ?>
<span class="shrink-0 text-right">
<span class="num block text-xl leading-none"><?= (int) $mutual['wins'] ?> <span class="text-zinc-600">:</span> <?= (int) $mutual['losses'] ?></span>
<small class="mt-1 block text-[10px] font-semibold tracking-wide text-zinc-500 uppercase">Vzájemně</small>
</span>
<?php endif; ?>
</a>
<form method="post" action="<?= e(url('/app/friends/' . (int) $link['id'] . '/delete')) ?>">
<input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
<button class="pill text-rose-300!" type="submit">Odebrat</button>
</form>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</section>

<?php else: ?>

<section class="panel mb-4" aria-labelledby="incoming-title">
<h2 id="incoming-title">Přišlé</h2>
<p class="mt-1 text-xs text-zinc-500">Tuhle žádost můžeš potvrdit tady, nebo v aplikaci.</p>
<?php if ($book['incoming'] === []): ?>
<p class="mt-4 text-sm text-zinc-500">Žádná nová žádost.</p>
<?php else: ?>
<ul class="mt-4 flex flex-col gap-4">
<?php foreach ($book['incoming'] as $link): ?>
<li class="flex flex-col gap-3 rounded-2xl bg-white/[.035] p-3 ring-1 ring-white/[.06] ring-inset sm:flex-row sm:items-center">
<?php $person($link['user']); ?>
<div class="flex gap-2">
<form method="post" action="<?= e(url('/app/friends/' . (int) $link['id'] . '/decline')) ?>">
<input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
<button class="btn-ghost h-9! px-4! text-xs!" type="submit">Odmítnout</button>
</form>
<form method="post" action="<?= e(url('/app/friends/' . (int) $link['id'] . '/accept')) ?>">
<input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
<button class="btn-primary h-9! px-4! text-xs!" type="submit"><?= dartiqo_i('check') ?>Přijmout</button>
</form>
</div>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</section>

<section class="panel" aria-labelledby="outgoing-title">
<h2 id="outgoing-title">Odeslané</h2>
<?php if ($book['outgoing'] === []): ?>
<p class="mt-4 text-sm text-zinc-500">Žádná čekající odeslaná žádost.</p>
<?php else: ?>
<ul class="mt-4 flex flex-col gap-3">
<?php foreach ($book['outgoing'] as $link): ?>
<li class="flex items-center gap-3">
<?php $person($link['user']); ?>
<form method="post" action="<?= e(url('/app/friends/' . (int) $link['id'] . '/cancel')) ?>">
<input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
<button class="pill" type="submit">Zrušit</button>
</form>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</section>
<?php endif; ?>
</main>
