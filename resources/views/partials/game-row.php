<?php
/** @var array<string, mixed> $game */
require_once __DIR__ . '/ui.php';
$style = dartiqo_result((string) $game['result']);
$attrs = $attrs ?? '';
$versus = preg_match('/^(\S+)\s+:\s+(\S+)$/u', (string) $game['score'], $scoreParts) === 1;
$youScore = $versus ? $scoreParts[1] : (string) $game['score'];
$oppScore = $versus ? $scoreParts[2] : '';
?>
<a class="group relative flex items-center gap-3 overflow-hidden rounded-2xl border border-white/[.06] bg-zinc-900/60 py-3 pr-3 pl-3 transition duration-200 hover:border-white/15 hover:bg-zinc-900 sm:gap-4 sm:pr-4" <?= $attrs ?> href="<?= e(url('/app/games/' . $game['id'])) ?>">
<span class="pointer-events-none absolute inset-y-0 right-0 w-40 bg-gradient-to-l <?= e($style['wash']) ?> to-transparent opacity-40 transition-opacity group-hover:opacity-80" aria-hidden="true"></span>
<span class="relative grid size-11 shrink-0 place-items-center rounded-xl text-sm font-bold <?= e($style['soft']) ?>" aria-hidden="true"><?= e($style['mark']) ?></span>
<span class="relative min-w-0 flex-1">
<strong class="block truncate text-sm font-semibold"><?= e((string) $game['title']) ?></strong>
<small class="mt-0.5 flex items-center gap-1.5 truncate text-xs text-zinc-500"><?php if (!empty($game['online'])): ?><span class="rounded-full bg-sky-400/15 px-1.5 py-0.5 text-[9px] font-black tracking-wide text-sky-300 uppercase">Online</span><?php endif; ?><span class="<?= e(dartiqo_game_tone((string) $game['tone'])) ?> px-1.5! py-0.5! text-[9px]!"><?= e((string) $game['mode']) ?></span><span class="truncate"><?= e((string) $game['meta']) ?></span></small>
</span>
<span class="relative shrink-0 text-right">
<span class="num block text-xl leading-none">
<?php if ($versus): ?>
<span class="<?= e($style['text']) ?>"><?= e($youScore) ?></span><span class="px-1 text-zinc-600">:</span><span class="text-zinc-300"><?= e($oppScore) ?></span>
<?php else: ?>
<span class="<?= e($style['text']) ?>"><?= e($youScore) ?></span>
<?php endif; ?>
</span>
<small class="mt-1 block text-[10px] font-semibold tracking-wide text-zinc-500 uppercase"><?= e((string) $game['label']) ?></small>
</span>
<?= dartiqo_i('chevron', 'relative hidden size-4 shrink-0 text-zinc-600 transition group-hover:translate-x-0.5 group-hover:text-zinc-300 sm:block') ?>
</a>
