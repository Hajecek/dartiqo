<?php

/** @var array<string, mixed> $link */

$summary = $link['summary'] ?? ['career' => ['played' => 0, 'average' => null], 'mutual' => ['wins' => 0, 'losses' => 0, 'draws' => 0]];
$mutual = $summary['mutual'];
$playedTogether = (int) $mutual['wins'] + (int) $mutual['losses'] + (int) $mutual['draws'];
$career = $summary['career'];
$friendUser = $link['user'];
$friendName = trim((string) $friendUser['first'] . ' ' . (string) $friendUser['last']);
if ($friendName === '') {
    $friendName = (string) $friendUser['username'];
}
$private = !empty($summary['private']) || !empty($friendUser['private']);
$hint = '@' . $friendUser['username'];
if ($private) {
    $hint .= ' · soukromý účet';
} elseif ($career['average'] !== null) {
    $hint .= ' · průměr ' . number_format((float) $career['average'], 1, '.', '');
} elseif ((int) $career['played'] > 0) {
    $hint .= ' · ' . (int) $career['played'] . ' zápasů';
}
$letter = mb_strtoupper(mb_substr($friendName, 0, 1));
$photo = (string) ($friendUser['photo'] ?? '');
?>
<li class="flex items-center gap-2 rounded-2xl py-1">
<a class="flex min-w-0 flex-1 items-center gap-3 rounded-2xl px-1 py-1 hover:bg-white/[.04]" href="<?= e(url('/app/friends/' . rawurlencode((string) $friendUser['publicId']))) ?>">
<?php if ($photo !== ''): ?>
<span class="grid size-11 shrink-0 overflow-hidden rounded-full bg-white/10"><img class="size-full object-cover" src="<?= e(url($photo)) ?>" alt=""></span>
<?php else: ?>
<span class="grid size-11 shrink-0 place-items-center rounded-full bg-accent text-sm font-bold text-black"><?= e($letter) ?></span>
<?php endif; ?>
<span class="min-w-0 flex-1">
<strong class="block truncate text-sm"><?= e($friendName) ?></strong>
<span class="mt-0.5 block truncate text-xs text-zinc-500"><?= e($hint) ?></span>
</span>
<?php if (!$private && $playedTogether > 0): ?>
<span class="shrink-0 text-right">
<span class="num block text-xl leading-none"><?= (int) $mutual['wins'] ?> <span class="text-zinc-600">:</span> <?= (int) $mutual['losses'] ?></span>
<small class="mt-1 block text-[10px] font-semibold tracking-wide text-zinc-500 uppercase">Vzájemně</small>
</span>
<?php endif; ?>
</a>
<button class="pill text-rose-300!" type="button" data-sheet="sheet-remove-friend" data-remove-action="<?= e(url('/app/friends/' . (int) $link['id'] . '/delete')) ?>" data-remove-name="<?= e($friendName) ?>">Odebrat</button>
</li>
