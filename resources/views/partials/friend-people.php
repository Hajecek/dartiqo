<?php

/** @var list<array<string, mixed>> $people */
/** @var string $query */
/** @var bool $searching */
/** @var string $csrf */

$query = (string) ($query ?? '');
$searching = (bool) ($searching ?? false);
$csrf = (string) ($csrf ?? '');

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
?>
<?php if ($people === []): ?>
<p class="text-sm text-zinc-500"><?= $searching ? 'Nikdo takový v Dartiqo není.' : 'Zatím tu není žádný další hráč.' ?></p>
<?php else: ?>
<ul class="flex flex-col gap-5">
<?php foreach ($people as $hit): ?>
<?php
    $relation = (string) ($hit['relation'] ?? 'none');
    $name = trim((string) $hit['first'] . ' ' . (string) $hit['last']);
    if ($name === '') {
        $name = (string) $hit['username'];
    }
?>
<li class="flex items-center gap-4 py-1">
<?= $avatar($hit) ?>
<span class="min-w-0 flex-1">
<strong class="block truncate text-sm"><?= e($name) ?></strong>
<span class="mt-0.5 block text-xs text-zinc-500">@<?= e((string) $hit['username']) ?></span>
</span>
<?php if ($relation === 'accepted'): ?>
<span class="text-xs font-semibold text-zinc-400">Kamarádi</span>
<?php elseif ($relation === 'outgoing'): ?>
<span class="text-xs font-semibold text-zinc-400">Odesláno</span>
<?php else: ?>
<form method="post" action="<?= e(url('/app/friends')) ?>">
<input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
<input type="hidden" name="userId" value="<?= (int) $hit['id'] ?>">
<input type="hidden" name="q" value="<?= e($query) ?>">
<input type="hidden" name="find" value="1">
<button class="btn-primary h-9! px-4! text-xs!" type="submit"><?= $relation === 'incoming' ? 'Přijmout' : 'Žádost' ?></button>
</form>
<?php endif; ?>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
