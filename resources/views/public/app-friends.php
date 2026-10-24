<?php
require_once __DIR__ . '/../partials/ui.php';

/** @var array{friends: list<array<string, mixed>>, incoming: list<array<string, mixed>>, outgoing: list<array<string, mixed>>} $book */
$folder = ($folder ?? 'friends') === 'requests' ? 'requests' : 'friends';
$query = (string) ($query ?? '');
$openFind = (bool) ($openFind ?? false);
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
<a class="<?= $folder === 'friends' ? 'active' : '' ?>" href="<?= e(url('/app/friends?folder=friends')) ?>">Kamarádi</a>
<a class="<?= $folder === 'requests' ? 'active' : '' ?>" href="<?= e(url('/app/friends?folder=requests')) ?>">Žádosti<?php if ($incomingCount > 0): ?><span class="grid h-5 min-w-5 place-items-center rounded-full bg-accent px-1.5 text-[10px] font-bold text-black"><?= $incomingCount ?></span><?php endif; ?></a>
</nav>

<?php if (is_string($flash) && $flash !== '' && !$openFind): ?>
<p class="mb-4 rounded-2xl bg-accent/10 px-4 py-3 text-sm text-accent" role="status"><?= e($flash) ?></p>
<?php endif; ?>

<button class="mb-6 flex h-12 w-full items-center gap-3 rounded-full bg-white/[.04] px-4 text-left text-sm text-zinc-400 ring-1 ring-white/10 ring-inset transition hover:bg-white/[.07] hover:text-zinc-200" type="button" data-sheet="sheet-find-player" data-open-find>
<?= dartiqo_i('search', 'size-4 shrink-0') ?>
<span>Najít hráče</span>
</button>

<?php if ($folder === 'friends'): ?>
<section class="panel" aria-labelledby="friends-title">
<h2 id="friends-title">Přijatí</h2>
<?php if ($book['friends'] === []): ?>
<p class="mt-4 text-sm text-zinc-500" id="friends-empty">Zatím tu nikdo není. Najdi hráče a pošli mu žádost.</p>
<?php else: ?>
<ul class="mt-4 flex flex-col gap-2" id="friends-list">
<?php foreach ($book['friends'] as $link): ?>
<?php require __DIR__ . '/../partials/friend-accepted.php'; ?>
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
<?php require __DIR__ . '/../partials/friend-find.php'; ?>
<?php require __DIR__ . '/../partials/friend-remove.php'; ?>
</main>
