<?php
/** @var array<string, mixed> $game */
$style = dartiqo_result((string) $game['result']);
$attrs = $attrs ?? '';
$versus = preg_match('/^(\S+)\s+:\s+(\S+)$/u', (string) $game['score'], $scoreParts) === 1;
$youScore = $versus ? $scoreParts[1] : (string) $game['score'];
$oppScore = $versus ? $scoreParts[2] : '';
$oppClass = $game['result'] === 'draw' ? $style['text'] : ($game['result'] === 'loss' ? 'text-emerald-300' : 'text-zinc-300');
$fade = match ($game['result']) {
    'win' => 'linear-gradient(to left, #00d29455 0%, #00d29422 40%, transparent 100%)',
    'loss' => 'linear-gradient(to left, #ff667f55 0%, #ff667f22 40%, transparent 100%)',
    'draw' => 'linear-gradient(to left, #ffd2364a 0%, #ffd2361c 40%, transparent 100%)',
    default => 'linear-gradient(to left, #faff004a 0%, #faff001c 40%, transparent 100%)',
};
?>
<a class="group relative flex items-center gap-3 overflow-hidden rounded-2xl border border-white/10 bg-zinc-900 py-3.5 pr-4 pl-4 hover:border-white/25" <?= $attrs ?> href="<?= e(url('/app/games/' . $game['id'])) ?>">
<span class="pointer-events-none absolute z-0" style="top:0;right:0;bottom:0;width:9rem;background:<?= e($fade) ?>" aria-hidden="true"></span>
<span class="pointer-events-none absolute top-1/2 right-4 z-0 flex -translate-y-1/2 items-center font-display text-3xl leading-none" aria-hidden="true">
<?php if ($versus): ?>
<span class="<?= e($style['text']) ?>"><?= e($youScore) ?></span><span class="px-1.5 text-zinc-600">:</span><span class="<?= e($oppClass) ?>"><?= e($oppScore) ?></span>
<?php else: ?>
<span class="<?= e($style['text']) ?>"><?= e($youScore) ?></span>
<?php endif; ?>
</span>
<span class="sr-only"><?= e((string) $game['score']) ?> <?= e((string) $game['label']) ?></span>
<span class="absolute inset-y-2 left-0 z-10 w-1 rounded-r-full <?= e($style['bar']) ?>" aria-hidden="true"></span>
<span class="relative z-10 grid size-10 shrink-0 place-items-center rounded-2xl text-sm font-bold <?= e($style['tile']) ?>" aria-hidden="true"><?= e($style['mark']) ?></span>
<span class="relative z-10 min-w-0 flex-1">
<strong class="block truncate"><?= e((string) $game['title']) ?></strong>
<small class="mt-0.5 block truncate text-xs text-zinc-500"><?= e((string) $game['mode']) ?> · <?= e((string) $game['meta']) ?></small>
</span>
</a>
