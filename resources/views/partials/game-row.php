<?php
/** @var array<string, mixed> $game */
$style = dartiqo_result((string) $game['result']);
$attrs = $attrs ?? '';
?>
<a class="group relative flex items-center gap-3 overflow-hidden rounded-2xl border border-white/10 bg-zinc-900 py-3 pr-4 pl-4 hover:border-white/25" <?= $attrs ?> href="<?= e(url('/app/games/' . $game['id'])) ?>">
<span class="absolute inset-y-2 left-0 w-1 rounded-r-full <?= e($style['bar']) ?>" aria-hidden="true"></span>
<span class="grid size-10 shrink-0 place-items-center rounded-2xl text-sm font-bold <?= e($style['tile']) ?>" aria-hidden="true"><?= e($style['mark']) ?></span>
<span class="min-w-0 flex-1">
<strong class="block truncate"><?= e((string) $game['title']) ?></strong>
<small class="mt-0.5 block truncate text-xs text-zinc-500"><?= e((string) $game['mode']) ?> · <?= e((string) $game['meta']) ?></small>
</span>
<span class="shrink-0 text-right">
<b class="block font-display text-lg leading-none"><?= e((string) $game['score']) ?></b>
<small class="mt-1 block text-[10px] font-bold uppercase tracking-wide <?= e($style['text']) ?>"><?= e((string) $game['label']) ?></small>
</span>
</a>
