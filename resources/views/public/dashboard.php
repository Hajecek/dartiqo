<?php
require_once __DIR__ . '/../partials/player-games.php';
require_once __DIR__ . '/../partials/ui.php';

/** @var \App\Services\MatchInsights $insights */
/** @var array{total:int,level:int,intoLevel:int,needed:int,rank:string} $progress */
$dash = $insights->dashboard();
$progress ??= \App\Services\PlayerXp::progress(0);
$recent = $insights->cards(4);
$form = $insights->form();
$last = $dash['last'];
$streakDays = $dash['week'];
$tasks = $dash['tasks'];
$tasksDone = count(array_filter($tasks, static fn (array $t): bool => $t[2]));
$nextTask = current(array_filter($tasks, static fn (array $t): bool => !$t[2]));
$fmt = static fn (int|float|null $value): string => $value === null ? '–' : number_format((float) $value, 1, '.', '');
$days = static fn (int $n): string => $n === 1 ? 'den' : ($n >= 2 && $n <= 4 ? 'dny' : 'dní');

$trend = $dash['trend'];
$trendValues = array_values(array_filter($trend, static fn (?float $v): bool => $v !== null));
$trendMax = $trendValues === [] ? 0 : max($trendValues);
$trendMin = $trendValues === [] ? 0 : min($trendValues) - 6;
$trendSpan = ($trendMax - $trendMin) ?: 1;
$monthAvg = $dash['monthAverage'];

$k = $dash['kpis'];
$kpis = [
    ['Průměr', $k['average']['value'], $k['average']['delta'], 'za 30 dní', '#faff00', null, '', dartiqo_line($k['average']['line'], '#faff00', ['ref' => $monthAvg !== null ? round($monthAvg, 1) : null])],
    ['Výhry', $k['wins']['value'], $k['wins']['delta'], 'za 30 dní', '#34d399', $k['wins']['value'] === '–' ? null : $k['wins']['rate'], $k['wins']['label'], dartiqo_line($k['wins']['line'], '#34d399', ['ref' => 50, 'refLabel' => '50 %', 'unit' => ' %'])],
    ['Zavírání', $k['closing']['value'], $k['closing']['delta'], 'na double za 30 dní', '#38bdf8', $k['closing']['value'] === '–' ? null : $k['closing']['rate'], $k['closing']['label'], $k['closing']['bars'] === [] ? '' : dartiqo_bars($k['closing']['bars'], '#38bdf8', ['unit' => ' %'])],
    ['Maximum 180', $k['max']['value'], $k['max']['delta'], 'za 30 dní', '#f472b6', null, '', $k['max']['bars'] === [] ? '' : dartiqo_bars($k['max']['bars'], '#f472b6', ['unit' => '×'])],
];
$subtitle = match (true) {
    $insights->isEmpty() => 'Spusť zápas v aplikaci a skóre uvidíš tady živě.',
    $tasksDone === 3 => 'Denní výzva je hotová. Pěkná práce.',
    default => 'Dnes ti ve výzvě zbývá ' . (3 - $tasksDone) . ' z 3 úkolů.',
};
?>
<main class="@container/dash mx-auto w-full max-w-7xl">
<header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="eyebrow"><?= e(dartiqo_today_label()) ?></p>
<h1 class="mt-2">Ahoj, <span data-player-first></span><span class="text-accent">.</span></h1>
<p class="mt-1 text-sm text-zinc-400"><?= e($subtitle) ?></p>
</div>
<div class="flex flex-wrap items-center gap-2">
<button class="pill" type="button" data-sheet="sheet-daily"><?= dartiqo_i('flag') ?><span>Denní výzva</span><b><?= 3 - $tasksDone ?></b></button>
<button class="pill" type="button" data-sheet="sheet-streak"><?= dartiqo_i('flame', 'text-orange-300') ?><span>Série</span><b><?= $dash['streak'] ?></b></button>
<?= dartiqo_theme_toggle() ?>
<button class="icon-btn text-rose-400 hover:bg-rose-400/10 hover:text-rose-300" type="button" data-logout aria-label="Odhlásit se" title="Odhlásit se"><?= dartiqo_i('logout') ?></button>
</div>
</header>

<?php if (($incomingFriends ?? 0) > 0): ?>
<a class="panel panel-link mb-4 flex items-center gap-4 p-4!" href="<?= e(url('/app/friends?folder=requests')) ?>">
<span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-accent text-black"><?= dartiqo_i('users', 'size-5') ?></span>
<span class="min-w-0 flex-1">
<strong class="block text-sm">Máš <?= (int) $incomingFriends === 1 ? 'novou žádost o přátelství' : ((int) $incomingFriends < 5 ? (int) $incomingFriends . ' žádosti o přátelství' : (int) $incomingFriends . ' žádostí o přátelství') ?></strong>
<span class="mt-0.5 block text-xs text-zinc-400">Otevři žádosti a potvrď je, nebo je odmítni.</span>
</span>
<?= dartiqo_i('arrow', 'size-4 shrink-0 text-zinc-500') ?>
</a>
<?php endif; ?>

<div id="live-hero" class="mb-4" data-href="<?= e(url('/app/live')) ?>" data-reload-on-finish hidden></div>

<div class="grid gap-4 @4xl/dash:grid-cols-12">

<a class="panel panel-link group p-0! @4xl/dash:col-span-8" href="<?= e(url('/app/profile')) ?>">
<span class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-accent/15 blur-3xl transition-opacity group-hover:opacity-80" aria-hidden="true"></span>
<span class="pointer-events-none absolute inset-0 bg-[linear-gradient(115deg,transparent_55%,#faff0008)]" aria-hidden="true"></span>
<div class="relative flex h-full flex-col gap-6 p-5 sm:p-7">
<div class="flex items-center gap-4">
<span class="relative shrink-0">
<span class="grid size-16 place-items-center overflow-hidden rounded-2xl bg-accent text-xl font-bold text-black shadow-[0_10px_30px_-10px_#faff00aa] [&_img]:size-full [&_img]:object-cover" data-player-avatar></span>
<span class="absolute -right-1.5 -bottom-1.5 grid size-7 place-items-center rounded-lg border-2 border-zinc-900 bg-zinc-100 text-[11px] font-bold text-zinc-950"><?= (int) $progress['level'] ?></span>
</span>
<span class="min-w-0 flex-1">
<strong class="block truncate text-lg font-semibold" data-player-full></strong>
<span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-400"><span class="chip bg-accent/10 text-accent ring-1 ring-accent/25 ring-inset"><?= e($progress['rank']) ?></span><span data-player-user></span></span>
</span>
<span class="icon-btn hidden sm:grid" aria-hidden="true"><?= dartiqo_i('arrow') ?></span>
</div>
<div class="mt-auto grid items-end gap-6 sm:grid-cols-[auto_1fr]">
<div>
<span class="eyebrow">Celkový průměr</span>
<div class="mt-1 flex items-end gap-3">
<strong class="num text-6xl leading-none sm:text-7xl <?= $dash['average'] === null ? 'text-zinc-600' : '' ?>"><?= e($fmt($dash['average'])) ?></strong>
<?php if ($dash['averageDelta'] !== '–'): $down = str_starts_with($dash['averageDelta'], '-'); ?>
<span class="chip mb-2 <?= $down ? 'bg-rose-400/10 text-rose-300' : 'bg-emerald-400/10 text-emerald-300' ?>"><?= dartiqo_i('up', 'size-3' . ($down ? ' rotate-180' : '')) ?><?= e(ltrim($dash['averageDelta'], '+-')) ?></span>
<?php endif; ?>
</div>
</div>
<div class="h-20 sm:h-24"><?= $dash['average'] === null ? '' : dartiqo_line($dash['averageLine'], '#faff00', ['ref' => round($dash['average'], 1), 'refLabel' => 'Ø ' . $fmt($dash['average']), 'peak' => true, 'grid' => 3]) ?></div>
</div>
<div>
<div class="mb-2 flex items-center justify-between text-xs"><span class="text-zinc-400">Level <?= (int) $progress['level'] ?> → <?= (int) $progress['level'] + 1 ?></span><span class="num text-zinc-300"><?= e(number_format((int) $progress['intoLevel'], 0, ',', ' ')) ?> <span class="text-zinc-500">/ <?= e(number_format((int) $progress['needed'], 0, ',', ' ')) ?> XP</span></span></div>
<div class="meter h-2"><i class="bg-gradient-to-r from-accent/60 to-accent shadow-[0_0_14px_#faff0080]" style="width:<?= e((string) ((int) $progress['needed'] === 0 ? 0 : round($progress['intoLevel'] / $progress['needed'] * 100, 1))) ?>%"></i></div>
</div>
</div>
</a>

<button class="group relative isolate flex flex-col overflow-hidden rounded-[28px] bg-accent p-5 text-left text-black shadow-[0_24px_60px_-28px_#faff00aa] transition duration-300 hover:-translate-y-0.5 sm:p-7 @4xl/dash:col-span-4" type="button" data-sheet="sheet-daily">
<span class="pointer-events-none absolute -right-10 -bottom-10 size-48 rounded-full border-[18px] border-black/[.06]" aria-hidden="true"></span>
<span class="pointer-events-none absolute -right-2 -bottom-2 size-24 rounded-full border-[12px] border-black/[.06]" aria-hidden="true"></span>
<span class="flex items-center justify-between">
<span class="text-[11px] font-semibold tracking-[.16em] uppercase opacity-60">Denní výzva</span>
<span class="grid size-9 place-items-center rounded-full bg-black/10 transition group-hover:bg-black group-hover:text-accent"><?= dartiqo_i('arrow', 'size-4') ?></span>
</span>
<span class="num mt-6 block text-6xl leading-none"><?= $tasksDone ?><span class="text-3xl opacity-40"> / 3</span></span>
<span class="mt-2 block text-sm font-medium opacity-70"><?= $nextTask ? 'Zbývá ti ' . e($nextTask[0]) . '.' : 'Všechny úkoly splněné.' ?></span>
<span class="mt-auto flex gap-1.5 pt-6"><?php foreach ($tasks as $task): ?><i class="h-1.5 flex-1 rounded-full <?= $task[2] ? 'bg-black' : 'bg-black/15' ?>"></i><?php endforeach; ?></span>
</button>

<section class="grid grid-cols-2 gap-4 @4xl/dash:col-span-12 @4xl/dash:grid-cols-4" aria-label="Základní statistiky za 30 dní">
<?php foreach ($kpis as [$label, $value, $delta, $note, $color, $ring, $ringLabel, $chart]): ?>
<article class="panel flex flex-col gap-4 p-4! sm:p-5!">
<div class="flex items-center justify-between gap-2"><span class="eyebrow tracking-[.12em]"><?= e($label) ?></span><span class="size-1.5 rounded-full" style="background:<?= e($color) ?>;box-shadow:0 0 10px <?= e($color) ?>"></span></div>
<div class="flex min-h-12 items-center justify-between gap-3">
<strong class="num text-3xl leading-none sm:text-4xl <?= $value === '–' ? 'text-zinc-600' : '' ?>"><?= e($value) ?></strong>
<?php if ($ring !== null): ?><?= dartiqo_ring($ring, $color, $ringLabel) ?><?php endif; ?>
</div>
<div class="mt-2 h-12"><?= $chart ?></div>
<small class="text-xs text-zinc-500"><b class="font-semibold text-zinc-300"><?= e($delta) ?></b> <?= e($note) ?></small>
</article>
<?php endforeach; ?>
</section>

<section class="panel @4xl/dash:col-span-8" aria-labelledby="trend-title">
<div class="flex flex-wrap items-end justify-between gap-3">
<div>
<p class="eyebrow">Posledních 30 dní</p>
<h2 class="mt-1" id="trend-title">Průměr po dnech</h2>
</div>
<a class="link-accent" href="<?= e(url('/app/statistics')) ?>">Všechny statistiky <?= dartiqo_i('arrow') ?></a>
</div>
<div class="relative mt-6 flex h-44 items-end gap-[3px] sm:gap-1" role="img" aria-label="Denní průměr na 3 šipky za posledních 30 dní">
<?php if ($monthAvg !== null): ?>
<span class="pointer-events-none absolute inset-x-0 border-t border-dashed border-white/15" style="bottom:<?= e((string) round((round($monthAvg, 1) - $trendMin) / $trendSpan * 100, 1)) ?>%" aria-hidden="true"><span class="absolute -top-5 right-0 text-[10px] text-zinc-500">Ø <?= e($fmt($monthAvg)) ?></span></span>
<?php endif; ?>
<?php foreach ($trend as $i => $value): $isLast = $i === count($trend) - 1; ?>
<?php if ($value === null): ?>
<span class="flex-1 rounded-t-md bg-white/[.04]" style="height:4%" title="Bez hry"></span>
<?php else: ?>
<span class="group/bar relative flex-1 rounded-t-md transition-colors <?= $isLast ? 'bg-accent shadow-[0_0_20px_#faff0066]' : ($value === $trendMax ? 'bg-accent/70' : 'bg-white/[.08] hover:bg-white/25') ?>" style="height:<?= e((string) max(4, round(($value - $trendMin) / $trendSpan * 100, 1))) ?>%" title="<?= e((string) $value) ?>">
<span class="pointer-events-none absolute -top-7 left-1/2 hidden -translate-x-1/2 rounded-md bg-zinc-100 px-1.5 py-0.5 text-[10px] font-semibold whitespace-nowrap text-zinc-950 group-hover/bar:block"><?= e((string) $value) ?></span>
</span>
<?php endif; ?>
<?php endforeach; ?>
</div>
<div class="mt-3 flex justify-between text-[10px] text-zinc-500"><span>před 30 dny</span><span>dnes</span></div>
</section>

<button class="panel panel-link flex flex-col text-left @4xl/dash:col-span-4" type="button" data-sheet="sheet-streak">
<span class="pointer-events-none absolute -top-16 -left-10 size-48 rounded-full bg-orange-400/10 blur-3xl" aria-hidden="true"></span>
<span class="flex items-center justify-between"><span class="eyebrow">Série</span><span class="grid size-9 place-items-center rounded-xl bg-orange-400/10 text-orange-300 ring-1 ring-orange-400/20 ring-inset"><?= dartiqo_i('flame', 'size-4') ?></span></span>
<span class="num mt-4 block text-5xl leading-none"><?= $dash['streak'] ?> <span class="text-2xl text-zinc-500"><?= $days($dash['streak']) ?></span></span>
<span class="mt-2 block text-sm text-zinc-400"><?= $dash['playedToday'] ? 'Dnešek máš odehraný. Sérii drž i zítra.' : 'Dnešek ještě chybí. Odehraj aspoň jeden zápas.' ?></span>
<span class="mt-auto grid grid-cols-7 gap-1.5 pt-6 text-center">
<?php foreach ($streakDays as $day): ?>
<span class="flex flex-col items-center gap-1.5">
<small class="text-[9px] font-semibold text-zinc-500"><?= e($day['label']) ?></small>
<i class="grid aspect-square w-full max-w-9 place-items-center rounded-xl text-[10px] not-italic <?= $day['trained'] ? 'bg-orange-400/90 text-black' : ($day['today'] ? 'border border-dashed border-orange-300/60 text-orange-300' : 'bg-white/[.05] text-zinc-600') ?>"><?= $day['trained'] ? '✓' : ($day['today'] ? '•' : '') ?></i>
</span>
<?php endforeach; ?>
</span>
</button>

<section class="panel @4xl/dash:col-span-8" aria-labelledby="recent-title">
<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
<div>
<p class="eyebrow">Naposledy</p>
<h2 class="mt-1" id="recent-title">Poslední hry</h2>
</div>
<?php if ($recent !== []): ?><a class="link-accent" href="<?= e(url('/app/games')) ?>">Všechny hry <?= dartiqo_i('arrow') ?></a><?php endif; ?>
</div>
<?php if ($recent !== []): ?>
<div class="flex flex-col gap-2">
<?php foreach ($recent as $game): ?>
<?php $attrs = ''; require __DIR__ . '/../partials/game-row.php'; ?>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-white/10 px-6 py-10 text-center">
<span class="grid size-12 place-items-center rounded-2xl bg-white/[.04] text-zinc-500 [&_svg]:size-5"><?= dartiqo_i('clock') ?></span>
<strong class="text-sm">Zatím žádné hry</strong>
<p class="max-w-sm text-xs text-zinc-500">Dohraj zápas v aplikaci a objeví se tady.</p>
</div>
<?php endif; ?>
</section>

<div class="flex flex-col gap-4 @4xl/dash:col-span-4">
<?php if ($last !== null): $lastStyle = dartiqo_result((string) $last['result']); $versus = preg_match('/^(\S+)\s+:\s+(\S+)$/u', (string) $last['score'], $lastScore) === 1; ?>
<a class="panel panel-link group block" href="<?= e(url('/app/games/' . $last['id'])) ?>">
<span class="pointer-events-none absolute -right-12 -bottom-12 size-44 rounded-full bg-emerald-400/10 blur-3xl" aria-hidden="true"></span>
<span class="flex items-center justify-between"><span class="eyebrow">Poslední zápas</span><span class="chip <?= e($lastStyle['soft']) ?>"><?= e((string) $last['label']) ?></span></span>
<span class="mt-5 flex items-center justify-between gap-3">
<span class="min-w-0"><small class="block text-[10px] font-semibold tracking-widest text-zinc-500 uppercase">Ty</small><strong class="block truncate text-sm" data-player-first></strong></span>
<?php if ($versus): ?>
<span class="num shrink-0 text-4xl leading-none"><span class="<?= e($lastStyle['text']) ?>"><?= e($lastScore[1]) ?></span><span class="px-1.5 text-zinc-600">:</span><span class="text-zinc-400"><?= e($lastScore[2]) ?></span></span>
<span class="min-w-0 text-right"><small class="block text-[10px] font-semibold tracking-widest text-zinc-500 uppercase">Soupeř</small><strong class="block truncate text-sm"><?= e((string) $last['title']) ?></strong></span>
<?php else: ?>
<span class="num shrink-0 text-4xl leading-none <?= e($lastStyle['text']) ?>"><?= e((string) $last['score']) ?></span>
<span class="min-w-0 text-right"><small class="block text-[10px] font-semibold tracking-widest text-zinc-500 uppercase">Hra</small><strong class="block truncate text-sm"><?= e((string) $last['title']) ?></strong></span>
<?php endif; ?>
</span>
<span class="mt-5 flex items-center justify-between border-t border-white/[.06] pt-4 text-xs text-zinc-400"><span class="truncate"><?= e(str_replace(' • ', ' · ', (string) $last['format'])) ?><?= $last['avg'] !== '–' ? ' · Ø ' . e((string) $last['avg']) : '' ?></span><?= dartiqo_i('arrow', 'size-4 shrink-0 text-zinc-500 transition group-hover:translate-x-0.5 group-hover:text-accent') ?></span>
</a>
<?php endif; ?>
<section class="panel" aria-labelledby="form-title">
<div class="flex items-center justify-between"><p class="eyebrow" id="form-title">Forma</p><span class="text-xs text-zinc-500">posledních 5</span></div>
<div class="mt-4 flex gap-2" aria-label="Forma výher a proher">
<?php for ($i = 0; $i < 5; $i++): $result = $form[$i] ?? null; ?>
<?php if ($result === null): ?>
<i class="h-11 flex-1 rounded-xl border border-dashed border-white/10" aria-hidden="true"></i>
<?php else: ?>
<i class="grid h-11 flex-1 place-items-center rounded-xl text-xs font-bold not-italic <?= $result === 'win' ? 'bg-emerald-400/90 text-black' : 'bg-white/[.05] text-zinc-500 ring-1 ring-white/[.06] ring-inset' ?>"><?= e(dartiqo_result($result)['mark']) ?></i>
<?php endif; ?>
<?php endfor; ?>
</div>
<?php $formWins = count(array_filter($form, static fn (string $r): bool => $r === 'win')); ?>
<p class="mt-3 text-xs text-zinc-500"><?= $form === [] ? 'Forma se spočítá z pěti posledních zápasů proti soupeři.' : e($formWins . ' ' . ($formWins === 1 ? 'výhra' : ($formWins >= 2 && $formWins <= 4 ? 'výhry' : 'výher')) . ' z ' . count($form)) . ' · úspěšnost <b class="text-zinc-300">' . (int) round($formWins / count($form) * 100) . ' %</b>' ?></p>
</section>
</div>

</div>
</main>

<dialog class="m-auto max-h-[min(860px,calc(100svh-2rem))] w-[min(520px,calc(100%-2rem))] overflow-auto rounded-[28px] border border-white/10 bg-zinc-950 p-5 text-left text-zinc-100 shadow-2xl backdrop:bg-black/70 backdrop:backdrop-blur-sm open:animate-reveal" id="sheet-daily">
<div class="mb-4 flex items-center justify-between gap-3"><p class="eyebrow">Dnes</p><button class="icon-btn size-9" type="button" data-sheet-close aria-label="Zavřít">×</button></div>
<div class="relative overflow-hidden rounded-3xl bg-accent p-6 text-black">
<span class="pointer-events-none absolute -right-8 -bottom-8 size-36 rounded-full border-[14px] border-black/[.06]" aria-hidden="true"></span>
<span class="text-[11px] font-semibold tracking-[.16em] uppercase opacity-60">Denní výzva</span>
<strong class="num mt-3 block text-6xl leading-none"><?= $tasksDone ?> <small class="text-2xl opacity-50">/ 3</small></strong>
<span class="mt-3 block text-sm opacity-75">Splň všechny tři v aplikaci. Za hotový úkol přibude 50 XP.</span>
<div class="mt-5 flex gap-1.5"><?php foreach ($tasks as $task): ?><i class="h-1.5 flex-1 rounded-full <?= $task[2] ? 'bg-black' : 'bg-black/15' ?>"></i><?php endforeach; ?></div>
</div>
<div class="mt-3 flex flex-col gap-2">
<?php foreach ($tasks as [$name, $meta, $done]): ?>
<div class="flex items-center gap-3 rounded-2xl p-3 <?= $done ? 'tile opacity-70' : 'bg-accent/[.06] ring-1 ring-accent/30 ring-inset' ?>">
<b class="grid size-9 shrink-0 place-items-center rounded-xl <?= $done ? 'bg-emerald-400/15 text-emerald-300' : 'bg-accent/15 text-accent' ?>"><?= $done ? dartiqo_i('check', 'size-4') : dartiqo_i('target', 'size-4') ?></b>
<span class="min-w-0 flex-1"><strong class="block text-sm <?= $done ? 'line-through decoration-zinc-600' : '' ?>"><?= e($name) ?></strong><small class="block text-xs text-zinc-500"><?= e($meta) ?></small></span>
<?php if (!$done): ?><span class="chip bg-accent text-black">+50 XP</span><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</dialog>

<dialog class="m-auto max-h-[min(860px,calc(100svh-2rem))] w-[min(520px,calc(100%-2rem))] overflow-auto rounded-[28px] border border-white/10 bg-zinc-950 p-5 text-left text-zinc-100 shadow-2xl backdrop:bg-black/70 backdrop:backdrop-blur-sm open:animate-reveal" id="sheet-streak">
<div class="mb-4 flex items-center justify-between gap-3"><p class="eyebrow">Série</p><button class="icon-btn size-9" type="button" data-sheet-close aria-label="Zavřít">×</button></div>
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-orange-300 to-orange-500 p-6 text-black">
<span class="text-[11px] font-semibold tracking-[.16em] uppercase opacity-60">Série hracích dní</span>
<strong class="num mt-3 block text-6xl leading-none"><?= $dash['streak'] ?> <small class="text-2xl opacity-50"><?= $days($dash['streak']) ?></small></strong>
<span class="mt-3 block text-sm opacity-75"><?= $dash['playedToday'] ? 'Dnešek máš odehraný.' : 'Dnešek ještě chybí. Sérii udržíš zápasem v aplikaci.' ?></span>
<div class="mt-5 grid grid-cols-7 gap-1.5 text-center">
<?php foreach ($streakDays as $day): ?>
<div>
<small class="block text-[9px] font-semibold opacity-60"><?= e($day['label']) ?></small>
<i class="mx-auto mt-1 grid aspect-square w-full max-w-9 place-items-center rounded-xl text-xs not-italic <?= $day['trained'] ? 'bg-black text-orange-300' : ($day['today'] ? 'border-2 border-dashed border-black/40' : 'bg-black/10') ?>"><?= $day['trained'] ? '✓' : '' ?></i>
</div>
<?php endforeach; ?>
</div>
</div>
<div class="mt-3 grid grid-cols-2 gap-2">
<div class="tile p-4"><span class="eyebrow">Nejdelší série</span><strong class="num mt-1 block text-2xl"><?= $dash['longestStreak'] ?> <?= $days($dash['longestStreak']) ?></strong></div>
<div class="tile p-4"><span class="eyebrow">Tento měsíc</span><strong class="num mt-1 block text-2xl"><?= $dash['monthDays'] ?> <?= $days($dash['monthDays']) ?></strong></div>
</div>
</dialog>
