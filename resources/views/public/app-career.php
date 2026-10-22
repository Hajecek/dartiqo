<?php
require_once __DIR__ . '/../partials/ui.php';

$tiers = [
    ['Amatér', 'Klubové večery', 'done'],
    ['Lokální soutěže', 'Páteční poháry', 'done'],
    ['Regionální turnaje', 'Krajské kvalifikace', 'current'],
    ['Národní úroveň', 'Mistrovství ČR', 'locked'],
    ['Profesionální okruh', 'Tour Challenge', 'locked'],
    ['Světová špička', 'World Finals', 'locked'],
];
$season = [
    'name' => 'Sezóna 2026 · Podzim',
    'points' => 1240,
    'rank' => 14,
    'played' => 9,
    'wins' => 6,
    'titles' => 2,
];
$levels = [
    'local' => ['Lokální', '#38bdf8'],
    'regional' => ['Regionální', '#faff00'],
    'national' => ['Národní', '#f472b6'],
];
$calendar = [
    ['2026-10-04', '18:00', 'Páteční pohár #38', 'Praha · Dart Bar 180', '501 · Double out', 'local', 32, 32, true],
    ['2026-10-11', '10:00', 'Krajská kvalifikace Olomouc', 'Olomouc · Sportcentrum', '501 · Best of 5', 'regional', 64, 64, true],
    ['2026-10-18', '17:00', 'Podzimní liga · 4. kolo', 'Online', '501 · Best of 3', 'local', 48, 48, true],
    ['2026-10-23', '19:00', 'Páteční pohár #39', 'Praha · Dart Bar 180', '501 · Double out', 'local', 32, 27, false],
    ['2026-10-25', '10:00', 'Krajský pohár Brno', 'Brno · Hala Vodova', '501 · Double out', 'regional', 32, 24, true],
    ['2026-10-25', '18:00', 'Nedělní blitz', 'Online', '301 · Best of 3', 'local', 64, 40, false],
    ['2026-10-31', '16:00', 'Halloween Open', 'Plzeň · Pub Šipka', '501 · Best of 5', 'regional', 64, 51, false],
    ['2026-11-01', '17:00', 'Podzimní liga · 5. kolo', 'Online', '501 · Best of 3', 'local', 48, 30, true],
    ['2026-11-07', '10:00', 'Velká cena Ostravy', 'Ostrava · Arena', '501 · Best of 5', 'regional', 96, 62, false],
    ['2026-11-14', '09:00', 'Kvalifikace MČR', 'Praha · O2 universum', '501 · Best of 7', 'national', 128, 97, false],
    ['2026-11-15', '09:00', 'Kvalifikace MČR · den 2', 'Praha · O2 universum', '501 · Best of 7', 'national', 64, 0, false],
    ['2026-11-20', '19:00', 'Páteční pohár #40', 'Praha · Dart Bar 180', '501 · Double out', 'local', 32, 12, false],
    ['2026-11-28', '10:00', 'Krajský pohár Liberec', 'Liberec · Home Credit', '501 · Best of 5', 'regional', 48, 19, false],
    ['2026-12-05', '10:00', 'Mikulášský turnaj', 'Brno · Hala Vodova', '501 · Best of 5', 'regional', 64, 8, false],
    ['2026-12-12', '09:00', 'Mistrovství ČR', 'Praha · O2 universum', '501 · Best of 9', 'national', 64, 0, false],
    ['2026-12-19', '18:00', 'Vánoční blitz', 'Online', '301 · Best of 3', 'local', 128, 22, false],
];
$today = '2026-10-22';
$calMonths = ['2026-10', '2026-11', '2026-12'];
$monthNames = ['leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'];
$weekdaysShort = ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So'];
$eventsByDay = [];
foreach ($calendar as $event) {
    $eventsByDay[$event[0]][] = $event;
}
$dayLabel = static function (string $date) use ($weekdaysShort, $monthNames): string {
    $ts = strtotime($date);

    return $weekdaysShort[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . '. ' . (int) date('n', $ts) . '.';
};
$results = [
    ['Podzimní liga · 4. kolo', '18. 10.', 'Vítěz', '1.', 61.8, 150],
    ['Krajská kvalifikace Olomouc', '11. 10.', 'Semifinále', '3.–4.', 57.2, 90],
    ['Páteční pohár #38', '4. 10.', 'Vítěz', '1.', 59.4, 120],
    ['Podzimní liga · 3. kolo', '27. 9.', 'Čtvrtfinále', '5.–8.', 54.1, 60],
    ['Open Ostrava', '20. 9.', 'Osmifinále', '9.–16.', 52.6, 40],
];
$rivals = [
    ['Martin Horák', '@horak'], ['Eva Černá', '@evac'], ['Jakub Svoboda', '@kuba501'], ['Ondřej Veselý', '@vesely'],
    ['Lenka Pokorná', '@lenka_p'], ['David Marek', '@marek9'], ['Filip Růžička', '@filipr'], ['Karel Fiala', '@fiala'],
    ['Michal Kolář', '@kolar_t20'], ['Veronika Novotná', '@vero'], ['Radek Zeman', '@zeman'], ['Petr Dvořák', '@dvorak_d20'],
    ['Jana Malá', '@janam'], null, ['Tomáš Král', '@kral180'], ['Lukáš Beneš', '@benes'],
    ['Adam Procházka', '@adamp'], ['Simona Kučerová', '@simik'], ['Vojtěch Hájek', '@hajek'], ['Roman Urban', '@urban'],
    ['Zdeněk Šťastný', '@stastny'], ['Kristýna Vlčková', '@kiki'], ['Jiří Musil', '@musil'], ['Patrik Bartoš', '@bartos'],
    ['Aneta Holubová', '@aneta'], ['Marek Sedláček', '@sedlacek'], ['Daniel Kratochvíl', '@kratoch'], ['Pavel Polák', '@polak'],
    ['Šárka Doležalová', '@sarka'], ['Josef Bláha', '@blaha'],
];
$ranking = [];
$meIndex = $season['rank'] - 1;
$step = static fn (int $i): int => 20 + ($i * 37) % 60;
foreach ($rivals as $i => $player) {
    $points = $season['points'];
    for ($j = $i; $j < $meIndex; $j++) {
        $points += $step($j);
    }
    for ($j = $meIndex; $j < $i; $j++) {
        $points -= $step($j);
    }
    $ranking[] = [
        'pos' => $i + 1,
        'name' => $player[0] ?? null,
        'user' => $player[1] ?? null,
        'points' => $points,
        'move' => [2, 0, -1, 1, 0, 3, -2, 0, 1, -1, 4, 0, -3, 2, 1, 0, -1, 5, 0, -2, 1, 0, -4, 2, 0, 1, -1, 0, 3, -2][$i],
        'avg' => $player === null ? 58.4 : round(66.8 - $i * 0.5 + sin($i * 1.7) * 1.6, 1),
        'events' => $player === null ? $season['played'] : 7 + ($i * 5) % 8,
    ];
}
$initials = static function (?string $name): string {
    if ($name === null) {
        return 'A';
    }
    $parts = explode(' ', $name);

    return mb_substr($parts[0], 0, 1) . mb_substr($parts[1] ?? '', 0, 1);
};
$me = $ranking[$meIndex];
$above = $ranking[$meIndex - 1] ?? null;
$ladder = array_slice($ranking, $season['rank'] - 3, 5);
$movePill = static function (int $move): string {
    if ($move === 0) {
        return '<span class="num w-8 shrink-0 text-right text-[11px] text-zinc-600">–</span>';
    }
    $up = $move > 0;

    return '<span class="num inline-flex w-8 shrink-0 items-center justify-end gap-0.5 text-[11px] ' . ($up ? 'text-emerald-300' : 'text-rose-300') . '">'
        . dartiqo_i('up', 'size-3' . ($up ? '' : ' rotate-180')) . abs($move) . '</span>';
};
$ladderRow = static function (array $row) use ($season): string {
    $isMe = $row['name'] === null;
    $gap = $row['points'] - $season['points'];
    $badge = [1 => 'bg-accent text-black', 2 => 'bg-zinc-200 text-black', 3 => 'bg-orange-400 text-black'][$row['pos']]
        ?? ($isMe ? 'bg-black text-[#faff00]' : ($gap > 0 ? 'bg-rose-400/15 text-rose-300' : 'bg-emerald-400/15 text-emerald-300'));
    $who = $isMe
        ? '<strong class="block truncate text-sm" data-player-full>Alex Novák</strong><small class="block truncate text-xs text-black/60" data-player-user>@alex180</small>'
        : '<strong class="block truncate text-sm">' . e($row['name']) . '</strong><small class="block truncate text-xs text-zinc-500">' . e($row['user']) . '</small>';

    return '<div class="flex items-center gap-3 rounded-2xl px-3 ' . ($isMe ? 'bg-accent py-3.5 text-black shadow-[0_14px_36px_-14px_#faff00]' : 'tile py-2.5') . '"' . ($isMe ? ' data-me' : '') . '>'
        . '<b class="num grid size-8 shrink-0 place-items-center rounded-lg text-xs ' . $badge . '">' . e((string) $row['pos']) . '</b>'
        . '<span class="min-w-0 flex-1">' . $who . '</span>'
        . '<span class="w-16 shrink-0 text-right">'
        . '<strong class="num block leading-tight ' . ($isMe ? 'text-base' : 'text-sm text-zinc-100') . '">' . e(number_format($row['points'], 0, ',', ' ')) . ' <small class="text-[10px] font-normal ' . ($isMe ? 'text-black/60' : 'text-zinc-500') . '">b.</small></strong>'
        . '<small class="num block text-[10px] leading-tight ' . ($isMe ? 'font-semibold text-black/60' : ($gap > 0 ? 'text-rose-300' : 'text-emerald-300')) . '">' . ($isMe ? 'ty' : ($gap > 0 ? '+' : '−') . e(number_format(abs($gap), 0, ',', ' '))) . '</small>'
        . '</span>'
        . '</div>';
};
$seasonStart = '2026-08-17';
$seasonLog = [
    ['2026-08-23', 'Letní liga · finále', 210],
    ['2026-08-30', 'Páteční pohár #35', 180],
    ['2026-09-06', 'Krajský pohár Zlín', 240],
    ['2026-09-13', 'Páteční pohár #36', 150],
    ['2026-09-20', 'Open Ostrava', 40],
    ['2026-09-27', 'Podzimní liga · 3. kolo', 60],
    ['2026-10-04', 'Páteční pohár #38', 120],
    ['2026-10-11', 'Krajská kvalifikace Olomouc', 90],
    ['2026-10-18', 'Podzimní liga · 4. kolo', 150],
];
$shortDate = static fn (string $date): string => (int) date('j', strtotime($date)) . '. ' . (int) date('n', strtotime($date)) . '.';
$chartDays = (strtotime($today) - strtotime($seasonStart)) / 86400;
$toX = static fn (string $date): float => round((strtotime($date) - strtotime($seasonStart)) / 86400 / $chartDays * 100, 2);
$running = 0;
$chartPoints = [];
foreach ($seasonLog as [$date, $name, $gain]) {
    $running += $gain;
    $chartPoints[] = ['date' => $date, 'name' => $name, 'gain' => $gain, 'total' => $running];
}
$chartMax = (int) ceil($running / 400) * 400;
$toY = static fn (int $points): float => round(100 - $points / $chartMax * 100, 2);
foreach ($chartPoints as &$point) {
    $point['x'] = $toX($point['date']);
    $point['y'] = $toY($point['total']);
}
unset($point);
$chartLine = '0,100 ' . implode(' ', array_map(static fn (array $p): string => $p['x'] . ',' . $p['y'], $chartPoints)) . ' 100,' . $toY($running);
$chartTicks = [[$seasonStart, 0]];
for ($m = strtotime(date('Y-m-01', strtotime($seasonStart . ' +1 month'))); $m < strtotime($today); $m = strtotime('+1 month', $m)) {
    $chartTicks[] = [date('Y-m-d', $m), $toX(date('Y-m-d', $m))];
}
?>
<main class="@container/dash mx-auto w-full max-w-7xl">
<header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
<div>
<p class="eyebrow">Tvůj příběh</p>
<h1 class="mt-2">Kariéra</h1>
<p class="mt-1 max-w-xl text-sm text-zinc-400">Sezóna, žebříček a kalendář turnajů. Všechna data jsou zatím ukázková.</p>
</div>
<div class="flex flex-wrap items-center gap-2">
<span class="pill"><?= dartiqo_i('trophy', 'text-accent') ?><span>Tituly</span><b><?= e((string) $season['titles']) ?></b></span>
<?= dartiqo_theme_toggle() ?>
</div>
</header>

<div class="grid gap-4 @4xl/dash:grid-cols-12">

<section class="panel flex flex-col sm:p-7! @4xl/dash:col-span-8" id="season-points" aria-labelledby="season-title">
<span class="pointer-events-none absolute -top-32 -left-24 -z-10 size-96 rounded-full bg-accent/20 blur-3xl" aria-hidden="true"></span>
<span class="pointer-events-none absolute -right-24 -bottom-32 -z-10 size-80 rounded-full bg-emerald-400/10 blur-3xl" aria-hidden="true"></span>
<div class="flex flex-wrap items-center justify-between gap-3">
<p class="eyebrow" id="season-title">Body · <?= e($season['name']) ?></p>
<span class="chip bg-accent text-black shadow-[0_6px_18px_-6px_#faff0080]"><?= dartiqo_i('trophy', 'size-3') ?>#<?= e((string) $season['rank']) ?> v žebříčku</span>
</div>
<div class="mt-5 flex flex-wrap items-end gap-x-5 gap-y-3">
<strong class="num block text-[5rem] leading-[.8] tracking-[-.06em] text-accent drop-shadow-[0_0_30px_#faff0040] sm:text-[7.5rem]" data-count><?= e(number_format($running, 0, ',', ' ')) ?></strong>
<small class="block pb-1 text-sm text-zinc-400">bodů za sezónu<span class="block text-xs text-zinc-500">stav k <?= e($shortDate($today)) ?></span></small>
</div>

<div class="mt-8 grid grid-cols-[2.5rem_minmax(0,1fr)] gap-x-2">
<div class="relative h-44 text-right text-[10px] text-zinc-500 sm:h-52" aria-hidden="true">
<?php for ($g = 0; $g <= 4; $g++): ?>
<span class="num absolute right-0 -translate-y-1/2" style="top:<?= e((string) (100 - $g * 25)) ?>%"><?= e(number_format($chartMax / 4 * $g, 0, ',', ' ')) ?></span>
<?php endfor; ?>
</div>
<div class="relative h-44 sm:h-52" role="img" aria-label="Vývoj bodů v sezóně od <?= e($shortDate($seasonStart)) ?> do <?= e($shortDate($today)) ?>: z 0 na <?= e((string) $running) ?>">
<svg class="absolute inset-0 h-full w-full overflow-visible" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" data-reveal>
<defs><linearGradient id="season-area" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#faff00" stop-opacity=".3"/><stop offset="1" stop-color="#faff00" stop-opacity="0"/></linearGradient></defs>
<?php for ($g = 0; $g <= 4; $g++): ?>
<line x1="0" x2="100" y1="<?= e((string) ($g * 25)) ?>" y2="<?= e((string) ($g * 25)) ?>" stroke="#ffffff0d" stroke-width="1" vector-effect="non-scaling-stroke"/>
<?php endfor; ?>
<?php foreach (array_slice($chartTicks, 1) as [, $tx]): ?>
<line x1="<?= e((string) $tx) ?>" x2="<?= e((string) $tx) ?>" y1="0" y2="100" stroke="#ffffff40" stroke-width="1" stroke-dasharray="2 4" vector-effect="non-scaling-stroke"/>
<?php endforeach; ?>
<polygon points="<?= e($chartLine) ?> 100,100" fill="url(#season-area)"/>
<polyline points="<?= e($chartLine) ?>" fill="none" stroke="#faff00" stroke-opacity=".18" stroke-width="8" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
<polyline points="<?= e($chartLine) ?>" fill="none" stroke="#faff00" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
</svg>
<?php foreach ($chartPoints as $i => $pt): $isLast = $i === count($chartPoints) - 1; ?>
<span class="group/pt absolute z-10 -translate-1/2 p-2" data-pt data-x="<?= e((string) $pt['x']) ?>" data-total="<?= e((string) $pt['total']) ?>" style="left:<?= e((string) $pt['x']) ?>%;top:<?= e((string) $pt['y']) ?>%">
<i class="block rounded-full ring-2 ring-zinc-900 <?= $isLast ? 'size-3.5 shadow-[0_0_14px_#faff00]' : 'size-2.5' ?>" style="background:#faff00"></i>
<b class="num pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 text-[10px] whitespace-nowrap <?= $isLast ? 'text-accent' : 'text-zinc-400' ?> <?= ($i % 3 !== 2) ? 'hidden sm:block' : '' ?>"><?= e(number_format($pt['total'], 0, ',', ' ')) ?></b>
<span class="pointer-events-none absolute top-full left-1/2 z-20 mt-1 hidden -translate-x-1/2 rounded-lg bg-zinc-100 px-2 py-1 text-left text-[10px] leading-tight whitespace-nowrap text-zinc-950 group-hover/pt:block"><b class="block"><?= e($shortDate($pt['date'])) ?> · <?= e(number_format($pt['total'], 0, ',', ' ')) ?> b.</b><?= e($pt['name']) ?></span>
</span>
<?php endforeach; ?>
</div>
<span></span>
<div class="relative mt-2 h-4 text-[10px] text-zinc-500" aria-hidden="true">
<?php foreach ($chartTicks as $i => [$tickDate, $tx]): ?>
<span class="num absolute whitespace-nowrap <?= $i === 0 ? '' : '-translate-x-1/2' ?>" style="left:<?= e((string) $tx) ?>%"><?= e($i === 0 ? $shortDate($tickDate) : $monthNames[(int) date('n', strtotime($tickDate)) - 1]) ?></span>
<?php endforeach; ?>
<span class="num absolute right-0 font-semibold whitespace-nowrap text-zinc-300">dnes</span>
</div>
</div>

<div class="mt-6 flex flex-wrap gap-x-5 gap-y-1 border-t border-white/[.06] pt-4 text-xs text-zinc-500">
<span><b class="num text-sm text-zinc-200"><?= e((string) $season['played']) ?></b> turnajů</span>
<span><b class="num text-sm text-emerald-300"><?= e((string) $season['wins']) ?></b> výher</span>
<span><b class="num text-sm text-accent"><?= e((string) $season['titles']) ?></b> tituly</span>
</div>
</section>
<script>
(() => {
    const root = document.getElementById('season-points');
    if (!root || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const counter = root.querySelector('[data-count]');
    const reveal = root.querySelector('[data-reveal]');
    const dots = [...root.querySelectorAll('[data-pt]')];
    const path = [[0, 0], ...dots.map((dot) => [Number(dot.dataset.x), Number(dot.dataset.total)])];
    path.push([100, path[path.length - 1][1]]);
    const valueAt = (x) => {
        for (let i = 1; i < path.length; i++) {
            const [x0, v0] = path[i - 1];
            const [x1, v1] = path[i];
            if (x <= x1) return x1 === x0 ? v1 : v0 + (v1 - v0) * (x - x0) / (x1 - x0);
        }
        return path[path.length - 1][1];
    };
    const format = (n) => Math.round(n).toLocaleString('cs-CZ');
    const duration = 2800;

    counter.style.minWidth = `${counter.offsetWidth}px`;
    counter.textContent = '0';
    reveal.style.clipPath = 'inset(-20% 100% -20% 0)';
    dots.forEach((dot) => {
        dot.style.opacity = '0';
        dot.style.transform = 'scale(0)';
        dot.style.transition = 'opacity .3s ease, transform .45s cubic-bezier(.3, 1.8, .5, 1)';
    });

    let done = false;
    const finish = () => {
        if (done) return;
        done = true;
        counter.textContent = format(path[path.length - 1][1]);
        reveal.style.clipPath = '';
        dots.forEach((dot) => { dot.style.opacity = '1'; dot.style.transform = ''; dot.style.transition = ''; });
    };

    const run = () => {
        let start = null;
        let reached = 0;
        setTimeout(finish, duration + 1200);
        const frame = (now) => {
            if (done) return;
            start ??= now;
            const t = Math.min(1, (now - start) / duration);
            const x = (1 - (1 - t) ** 2) * 100;
            counter.textContent = format(valueAt(x));
            reveal.style.clipPath = `inset(-20% ${100 - x}% -20% 0)`;
            while (reached < dots.length && x >= Number(dots[reached].dataset.x)) {
                dots[reached].style.opacity = '1';
                dots[reached].style.transform = 'scale(1)';
                reached++;
            }
            if (t < 1) {
                requestAnimationFrame(frame);
            } else {
                finish();
            }
        };
        requestAnimationFrame(frame);
    };

    new IntersectionObserver((entries, observer) => {
        if (!entries.some((entry) => entry.isIntersecting)) return;
        observer.disconnect();
        setTimeout(run, 250);
    }, { threshold: .35 }).observe(root);
})();
</script>

<section class="panel flex flex-col @4xl/dash:col-span-4" aria-labelledby="ladder-title">
<span class="pointer-events-none absolute -top-24 -right-16 -z-10 size-64 rounded-full bg-accent/15 blur-3xl" aria-hidden="true"></span>
<div class="mb-4 flex items-center justify-between gap-3">
<div><p class="eyebrow">Žebříček</p><h2 class="mt-1" id="ladder-title">Kolem tebe</h2></div>
<span class="chip bg-white/[.06] text-zinc-300"><?= e((string) count($ranking)) ?> hráčů</span>
</div>
<div class="tile mb-4 flex items-end justify-between gap-3 p-4">
<div>
<small class="eyebrow block tracking-[.1em]">Tvoje pozice</small>
<strong class="num mt-1 block text-5xl leading-none tracking-[-.04em] text-accent">#<?= e((string) $me['pos']) ?><span class="text-lg font-medium text-zinc-500"> / <?= e((string) count($ranking)) ?></span></strong>
</div>
<?php if ($above): ?>
<div class="text-right">
<small class="eyebrow block tracking-[.1em]">Na <?= e((string) $above['pos']) ?>. místo</small>
<strong class="num mt-1 block text-xl leading-none text-rose-300"><?= e(number_format($above['points'] - $me['points'], 0, ',', ' ')) ?> <small class="text-xs font-medium text-zinc-500">b.</small></strong>
</div>
<?php endif; ?>
</div>
<div class="flex flex-col gap-2">
<?php foreach ($ladder as $row): ?>
<?= $ladderRow($row) ?>
<?php endforeach; ?>
</div>
<button class="link-accent mt-auto self-start pt-4" type="button" data-sheet="sheet-ranking">Celý žebříček <?= dartiqo_i('arrow') ?></button>
</section>

<section class="panel @4xl/dash:col-span-12" aria-labelledby="path-title">
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
<div><p class="eyebrow">Kariérní mapa</p><h2 class="mt-1" id="path-title">Cesta na vrchol</h2></div>
<span class="text-xs text-zinc-500">Úroveň <b class="text-zinc-200">3</b> / <?= e((string) count($tiers)) ?></span>
</div>
<ol class="grid grid-cols-2 gap-2 @3xl/dash:grid-cols-3 @5xl/dash:grid-cols-6">
<?php foreach ($tiers as $i => [$label, $event, $state]): ?>
<li class="flex flex-col gap-3 rounded-2xl p-4 <?= $state === 'current' ? 'bg-accent/[.08] ring-1 ring-accent/40 ring-inset' : ($state === 'done' ? 'tile' : 'tile opacity-50') ?>">
<span class="flex items-center justify-between">
<b class="num text-xs text-zinc-500">0<?= e((string) ($i + 1)) ?></b>
<span class="grid size-7 place-items-center rounded-full <?= $state === 'done' ? 'bg-emerald-400/15 text-emerald-300' : ($state === 'current' ? 'bg-accent text-black' : 'bg-white/10 text-zinc-500') ?>"><?= dartiqo_i($state === 'done' ? 'check' : ($state === 'current' ? 'target' : 'lock'), 'size-3.5') ?></span>
</span>
<span><strong class="block text-sm"><?= e($label) ?></strong><small class="block text-xs text-zinc-500"><?= e($event) ?></small></span>
</li>
<?php endforeach; ?>
</ol>
</section>

<section class="panel scroll-mt-6 @4xl/dash:col-span-12" id="career-cal" aria-labelledby="calendar-title" data-start="0">
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
<div><p class="eyebrow">Kalendář</p><h2 class="mt-1" id="calendar-title">Turnaje v sezóně</h2></div>
<div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-zinc-400">
<?php foreach ($levels as [$label, $color]): ?>
<span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full" style="background:<?= e($color) ?>"></i><?= e($label) ?></span>
<?php endforeach; ?>
<span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full ring-2 ring-emerald-400 ring-inset"></i>Přihlášen</span>
</div>
</div>

<div class="grid gap-6 @6xl/dash:grid-cols-[minmax(0,1.75fr)_minmax(0,1fr)]">
<div class="@container/cal min-w-0">
<div class="mb-4 flex items-center justify-between gap-3">
<?php foreach ($calMonths as $i => $month): ?>
<h3 class="text-2xl font-semibold capitalize sm:text-3xl" data-cal-label="<?= e((string) $i) ?>"<?= $i === 0 ? '' : ' hidden' ?>><?= e($monthNames[(int) substr($month, 5, 2) - 1]) ?> <span class="text-zinc-500"><?= e(substr($month, 0, 4)) ?></span></h3>
<?php endforeach; ?>
<div class="seg shrink-0 p-0.5">
<button class="w-10 justify-center px-0! disabled:opacity-30" type="button" data-cal-prev aria-label="Předchozí měsíc"><?= dartiqo_i('back', 'size-4') ?></button>
<button class="w-10 justify-center px-0! disabled:opacity-30" type="button" data-cal-next aria-label="Další měsíc"><?= dartiqo_i('chevron', 'size-4') ?></button>
</div>
</div>
<div class="grid grid-cols-7 gap-1.5 pb-2 text-[10px] font-semibold tracking-widest text-zinc-500 uppercase @2xl/cal:gap-2 @2xl/cal:text-[11px]">
<?php foreach (['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne'] as $wd): ?><span class="px-1 text-center @2xl/cal:px-2 @2xl/cal:text-left"><?= e($wd) ?></span><?php endforeach; ?>
</div>
<?php foreach ($calMonths as $i => $month):
    $first = strtotime($month . '-01');
    $lead = ((int) date('N', $first)) - 1;
    $daysInMonth = (int) date('t', $first);
    $trail = (7 - ($lead + $daysInMonth) % 7) % 7;
?>
<div class="grid grid-cols-7 gap-1.5 @2xl/cal:gap-2" data-cal-month="<?= e($month) ?>"<?= $i === 0 ? '' : ' hidden' ?>>
<?php for ($k = 0; $k < $lead; $k++): ?><span class="rounded-2xl bg-white/[.015]"></span><?php endfor; ?>
<?php for ($d = 1; $d <= $daysInMonth; $d++):
    $date = $month . '-' . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
    $dayEvents = $eventsByDay[$date] ?? [];
    $isToday = $date === $today;
    $isPast = $date < $today;
    $isWeekend = (int) date('N', strtotime($date)) >= 6;
    $number = '<span class="num grid size-7 place-items-center rounded-full text-sm font-semibold ' . ($isToday ? 'bg-accent text-black' : ($isPast ? 'text-zinc-600' : ($isWeekend ? 'text-zinc-300' : 'text-zinc-200'))) . '">' . e((string) $d) . '</span>';
?>
<?php if ($dayEvents): ?>
<button class="group flex min-h-16 min-w-0 flex-col items-center gap-1.5 rounded-2xl bg-white/[.05] p-1.5 text-left ring-1 ring-white/[.08] transition ring-inset hover:bg-white/[.09] @2xl/cal:min-h-28 @2xl/cal:items-stretch @2xl/cal:p-2 [&.is-selected]:bg-accent/[.08] [&.is-selected]:ring-2 [&.is-selected]:ring-accent <?= $isPast ? 'opacity-60' : '' ?>" type="button" data-day="<?= e($date) ?>" data-day-label="<?= e($dayLabel($date)) ?>" aria-label="<?= e($dayLabel($date) . ', turnajů: ' . count($dayEvents)) ?>">
<?= $number ?>
<span class="flex gap-1 @2xl/cal:hidden">
<?php foreach ($dayEvents as $ev): ?><i class="size-1.5 rounded-full" style="background:<?= e($levels[$ev[5]][1]) ?>"></i><?php endforeach; ?>
</span>
<span class="hidden min-w-0 flex-col gap-1 @2xl/cal:flex">
<?php foreach ($dayEvents as $ev): $c = $levels[$ev[5]][1]; ?>
<span class="flex min-w-0 items-center gap-1.5 rounded-lg border-l-2 bg-black/30 px-2 py-1 text-[11px] leading-tight" style="border-color:<?= e($c) ?>">
<span class="min-w-0 flex-1"><b class="num block font-semibold text-zinc-400"><?= e($ev[1]) ?></b><span class="block truncate font-medium text-zinc-100"><?= e($ev[2]) ?></span></span>
<?php if ($ev[8]): ?><?= dartiqo_i('check', 'size-3 shrink-0 text-emerald-300') ?><?php endif; ?>
</span>
<?php endforeach; ?>
</span>
</button>
<?php else: ?>
<span class="flex min-h-16 flex-col items-center rounded-2xl p-1.5 ring-1 ring-white/[.04] ring-inset @2xl/cal:min-h-28 @2xl/cal:items-start @2xl/cal:p-2 <?= $isToday ? 'bg-accent/[.05] ring-accent/40!' : 'bg-white/[.02]' ?>"><?= $number ?></span>
<?php endif; ?>
<?php endfor; ?>
<?php for ($k = 0; $k < $trail; $k++): ?><span class="rounded-2xl bg-white/[.015]"></span><?php endfor; ?>
</div>
<?php endforeach; ?>
<p class="mt-4 text-xs text-zinc-500">Dnes je <b class="text-zinc-300"><?= e($dayLabel($today)) ?></b>. Klepni na den s turnajem pro detail.</p>
</div>

<div class="flex min-w-0 flex-col">
<div class="mb-3 flex items-center justify-between gap-3">
<h3 data-cal-title>Turnaje v měsíci</h3>
<button class="link-accent text-xs" type="button" data-cal-reset hidden>Celý měsíc</button>
</div>
<div class="flex flex-col gap-2">
<?php foreach ($calendar as [$date, $time, $name, $place, $format, $level, $capacity, $registered, $mine]):
    $isPast = $date < $today;
    $full = $registered >= $capacity;
    [$levelLabel, $levelColor] = $levels[$level];
    $ts = strtotime($date);
?>
<article class="flex items-center gap-3 rounded-2xl p-3 <?= $mine && !$isPast ? 'bg-emerald-400/[.06] ring-1 ring-emerald-400/25 ring-inset' : 'tile' ?> <?= $isPast ? 'opacity-55' : '' ?>" data-event data-date="<?= e($date) ?>"<?= str_starts_with($date, $calMonths[0]) ? '' : ' hidden' ?>>
<span class="grid w-12 shrink-0 place-items-center rounded-xl bg-white/[.05] py-2 text-center leading-none">
<small class="text-[9px] font-semibold text-zinc-500 uppercase"><?= e($weekdaysShort[(int) date('w', $ts)]) ?></small>
<b class="num mt-1 text-lg"><?= e(date('j', $ts)) ?></b>
</span>
<span class="min-w-0 flex-1">
<span class="flex items-center gap-1.5 text-[10px] font-semibold tracking-wider uppercase" style="color:<?= e($levelColor) ?>"><i class="size-1.5 rounded-full" style="background:<?= e($levelColor) ?>"></i><?= e($levelLabel) ?></span>
<strong class="mt-0.5 block truncate text-sm"><?= e($name) ?></strong>
<small class="block truncate text-xs text-zinc-500"><?= e($time) ?> · <?= e($place) ?> · <?= e($format) ?></small>
</span>
<span class="shrink-0 text-right">
<?php if ($isPast): ?>
<span class="chip bg-white/[.06] text-zinc-400">Odehráno</span>
<?php elseif ($mine): ?>
<span class="chip bg-emerald-400/15 text-emerald-300"><?= dartiqo_i('check', 'size-3') ?>Přihlášen</span>
<?php elseif ($full): ?>
<span class="chip bg-rose-400/10 text-rose-300">Plno</span>
<?php else: ?>
<button class="chip bg-accent text-black transition hover:brightness-110" type="button">Přihlásit</button>
<?php endif; ?>
<small class="num mt-1 block text-[10px] text-zinc-500"><?= e((string) $registered) ?>/<?= e((string) $capacity) ?> hráčů</small>
</span>
</article>
<?php endforeach; ?>
<p class="tile p-4 text-center text-xs text-zinc-500" data-cal-empty hidden>V tomto měsíci zatím nejsou žádné turnaje.</p>
</div>
</div>
</div>
</section>

<section class="panel @4xl/dash:col-span-12" aria-labelledby="results-title">
<div class="mb-4 flex items-end justify-between gap-3">
<div><p class="eyebrow">Sezóna</p><h2 class="mt-1" id="results-title">Odehrané turnaje</h2></div>
<span class="text-xs text-zinc-500">posledních <?= e((string) count($results)) ?></span>
</div>
<div class="flex flex-col gap-2">
<?php foreach ($results as [$name, $date, $stage, $place, $avg, $points]): $won = $place === '1.'; ?>
<div class="tile flex items-center gap-3 px-3 py-3">
<b class="grid size-10 shrink-0 place-items-center rounded-xl text-xs <?= $won ? 'bg-accent text-black' : 'bg-white/[.06] text-zinc-300' ?>"><?= $won ? dartiqo_i('trophy', 'size-4') : e($place) ?></b>
<span class="min-w-0 flex-1"><strong class="block truncate text-sm"><?= e($name) ?></strong><small class="block text-xs text-zinc-500"><?= e($date) ?> · <?= e($stage) ?> · Ø <?= e(number_format($avg, 1, '.', '')) ?></small></span>
<span class="num shrink-0 text-sm text-emerald-300">+<?= e((string) $points) ?></span>
</div>
<?php endforeach; ?>
</div>
</section>

</div>
<p class="mt-6 text-xs text-zinc-600">Náhled kariéry. Turnaje, body i žebříček jsou testovací data.</p>
</main>

<?php
$podiumStyles = [
    1 => ['h-28', 'bg-accent text-black shadow-[0_0_40px_-8px_#faff0099]', 'ring-accent'],
    2 => ['h-20', 'bg-zinc-200 text-black', 'ring-zinc-200'],
    3 => ['h-14', 'bg-orange-400 text-black', 'ring-orange-400'],
];
$tableCols = 'grid grid-cols-[2rem_minmax(0,1fr)_2.5rem_4.5rem] items-center gap-3 sm:grid-cols-[2rem_minmax(0,1fr)_3.5rem_3.5rem_2.5rem_4.5rem]';
?>
<dialog class="m-auto w-[min(640px,calc(100%-2rem))] overflow-hidden rounded-[28px] border border-white/10 bg-zinc-950 p-0 text-left text-zinc-100 shadow-2xl backdrop:bg-black/70 backdrop:backdrop-blur-sm open:animate-reveal" id="sheet-ranking">
<div class="flex max-h-[min(860px,calc(100svh-2rem))] flex-col">

<div class="flex shrink-0 items-start justify-between gap-3 border-b border-white/[.06] px-5 py-4 sm:px-7">
<div><p class="eyebrow"><?= e($season['name']) ?></p><h2 class="mt-1">Žebříček sezóny</h2></div>
<button class="icon-btn size-9" type="button" data-sheet-close aria-label="Zavřít">×</button>
</div>

<div class="min-h-0 flex-1 overflow-y-auto">
<div class="relative overflow-hidden px-5 pt-6 sm:px-7">
<span class="pointer-events-none absolute -top-40 left-1/2 size-80 -translate-x-1/2 rounded-full bg-accent/15 blur-3xl" aria-hidden="true"></span>
<ol class="relative grid grid-cols-3 items-end gap-2 sm:gap-3" aria-label="První tři místa">
<?php foreach ([1, 0, 2] as $idx): $row = $ranking[$idx]; [$height, $block, $ring] = $podiumStyles[$row['pos']]; ?>
<li class="flex min-w-0 flex-col items-center text-center">
<span class="grid place-items-center rounded-full bg-zinc-900 font-bold ring-2 <?= e($ring) ?> <?= $row['pos'] === 1 ? 'size-14 text-base' : 'size-12 text-sm' ?>"><?= e($initials($row['name'])) ?></span>
<strong class="mt-2 block w-full truncate text-sm"><?= e($row['name']) ?></strong>
<small class="num block text-xs text-zinc-400"><?= e(number_format($row['points'], 0, ',', ' ')) ?> b.</small>
<span class="mt-3 grid w-full place-items-center rounded-t-2xl <?= e($height . ' ' . $block) ?>"><b class="num text-3xl leading-none"><?= e((string) $row['pos']) ?></b></span>
</li>
<?php endforeach; ?>
</ol>
</div>

<div class="<?= e($tableCols) ?> sticky top-0 z-10 border-y border-white/[.06] bg-zinc-950 px-5 py-2.5 text-[10px] font-semibold tracking-widest text-zinc-500 uppercase sm:px-7">
<span>#</span><span>Hráč</span><span class="hidden text-right sm:block">Ø</span><span class="hidden text-right sm:block">Turn.</span><span class="text-right">Týden</span><span class="text-right">Body</span>
</div>
<div class="px-5 pb-2 sm:px-7">
<?php foreach (array_slice($ranking, 3) as $row): $isMe = $row['name'] === null; ?>
<div class="<?= e($tableCols) ?> -mx-3 rounded-xl border-b border-white/[.04] px-3 py-2.5 last:border-0 <?= $isMe ? 'border-transparent bg-accent/[.08] ring-1 ring-accent/30 ring-inset' : 'hover:bg-white/[.03]' ?>">
<b class="num text-sm <?= $isMe ? 'text-accent' : 'text-zinc-500' ?>"><?= e((string) $row['pos']) ?></b>
<span class="flex min-w-0 items-center gap-3">
<span class="grid size-8 shrink-0 place-items-center overflow-hidden rounded-full text-[11px] font-bold [&_img]:size-full [&_img]:object-cover <?= $isMe ? 'bg-accent text-black' : 'bg-white/[.06] text-zinc-300' ?>"<?= $isMe ? ' data-player-avatar' : '' ?>><?= e($initials($row['name'])) ?></span>
<?php if ($isMe): ?>
<span class="min-w-0"><strong class="block truncate text-sm" data-player-full>Alex Novák</strong><small class="block truncate text-xs text-zinc-500" data-player-user>@alex180</small></span>
<?php else: ?>
<span class="min-w-0"><strong class="block truncate text-sm font-medium"><?= e($row['name']) ?></strong><small class="block truncate text-xs text-zinc-500"><?= e($row['user']) ?></small></span>
<?php endif; ?>
</span>
<span class="num hidden text-right text-sm text-zinc-400 sm:block"><?= e(number_format($row['avg'], 1, '.', '')) ?></span>
<span class="num hidden text-right text-sm text-zinc-400 sm:block"><?= e((string) $row['events']) ?></span>
<?= $movePill($row['move']) ?>
<strong class="num text-right text-sm <?= $isMe ? 'text-accent' : '' ?>"><?= e(number_format($row['points'], 0, ',', ' ')) ?></strong>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="shrink-0 border-t border-white/[.06] bg-zinc-900/80 p-3 sm:px-5">
<div class="flex items-center gap-3 rounded-2xl bg-accent px-4 py-3 text-black">
<b class="num text-2xl leading-none">#<?= e((string) $me['pos']) ?></b>
<span class="min-w-0 flex-1"><strong class="block truncate text-sm">Tvoje pozice</strong><small class="block truncate text-xs opacity-70"><?= $above ? e(number_format($above['points'] - $me['points'], 0, ',', ' ')) . ' b. na ' . e((string) $above['pos']) . '. místo' : 'Vedeš žebříček' ?></small></span>
<strong class="num shrink-0 text-lg"><?= e(number_format($me['points'], 0, ',', ' ')) ?> <small class="text-xs font-medium opacity-70">b.</small></strong>
</div>
</div>

</div>
</dialog>

<script>
(() => {
    const root = document.getElementById('career-cal');
    if (!root) return;
    const months = [...root.querySelectorAll('[data-cal-month]')];
    const labels = [...root.querySelectorAll('[data-cal-label]')];
    const events = [...root.querySelectorAll('[data-event]')];
    const title = root.querySelector('[data-cal-title]');
    const reset = root.querySelector('[data-cal-reset]');
    const empty = root.querySelector('[data-cal-empty]');
    const prev = root.querySelector('[data-cal-prev]');
    const next = root.querySelector('[data-cal-next]');
    let month = Number(root.dataset.start) || 0;
    let day = null;

    const render = () => {
        months.forEach((el, i) => { el.hidden = i !== month; });
        labels.forEach((el, i) => { el.hidden = i !== month; });
        prev.disabled = month === 0;
        next.disabled = month === months.length - 1;
        const key = months[month].dataset.calMonth;
        let shown = 0;
        events.forEach((el) => {
            const visible = day ? el.dataset.date === day : el.dataset.date.startsWith(key);
            el.hidden = !visible;
            if (visible) shown++;
        });
        root.querySelectorAll('[data-day]').forEach((el) => el.classList.toggle('is-selected', el.dataset.day === day));
        const selected = day && root.querySelector(`[data-day="${day}"]`);
        title.textContent = selected ? `Turnaje · ${selected.dataset.dayLabel}` : 'Turnaje v měsíci';
        reset.hidden = !day;
        empty.hidden = shown > 0;
    };

    prev.addEventListener('click', () => { month = Math.max(0, month - 1); day = null; render(); });
    next.addEventListener('click', () => { month = Math.min(months.length - 1, month + 1); day = null; render(); });
    reset.addEventListener('click', () => { day = null; render(); });
    root.querySelectorAll('[data-day]').forEach((el) => el.addEventListener('click', () => {
        day = day === el.dataset.day ? null : el.dataset.day;
        render();
    }));
    render();
})();
</script>
