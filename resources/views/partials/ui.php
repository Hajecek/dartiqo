<?php

if (!function_exists('dartiqo_icon')) {
    function dartiqo_icon(string $d, string $class = ''): string
    {
        $cls = $class !== '' ? ' class="' . e($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' . $d . '"/></svg>';
    }

    /** @return array<string, string> */
    function dartiqo_icons(): array
    {
        return [
            'flag' => 'M5 21V4h9l-1.2 4H19l-8 13h1.2L5 21Z',
            'flame' => 'M12 3s4 4.2 4 8a4 4 0 0 1-8 0c0-1.2.6-2.2 1.2-3.1C8.4 10 8 12 9.2 13.2 8.4 9.5 12 6.2 12 3Z',
            'gear' => 'M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Z M12 3.5v2 M12 18.5v2 M4.8 6.2l1.5 1.5 M17.7 16.3l1.5 1.5 M3.5 12h2 M18.5 12h2 M4.8 17.8l1.5-1.5 M17.7 7.7l1.5-1.5',
            'arrow' => 'M5 12h14 M13 6l6 6-6 6',
            'chevron' => 'M9 6l6 6-6 6',
            'up' => 'M7 14l5-5 5 5',
            'target' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z M12 12h.01',
            'trophy' => 'M8 4h8v5a4 4 0 0 1-8 0V4Z M8 6H5a3 3 0 0 0 3 4 M16 6h3a3 3 0 0 1-3 4 M12 13v4 M8 20h8',
            'bolt' => 'M13 3 5 14h6l-1 7 8-11h-6l1-7Z',
            'chart' => 'M4 19V9 M10 19V5 M16 19v-6 M22 19V8',
            'search' => 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z M20 20l-4-4',
            'camera' => 'M4 8h3l2-3h6l2 3h3v11H4V8Z M12 16.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z',
            'pencil' => 'M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z M15 5l4 4',
            'moon' => 'M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5Z',
            'sun' => 'M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z M12 2.5v2 M12 19.5v2 M4.6 4.6l1.4 1.4 M18 18l1.4 1.4 M2.5 12h2 M19.5 12h2 M4.6 19.4 6 18 M18 6l1.4-1.4',
            'bell' => 'M6 16V11a6 6 0 1 1 12 0v5l2 2H4l2-2Z M10 20a2 2 0 0 0 4 0',
            'mail' => 'M4 6h16v12H4V6Z M4 7l8 6 8-6',
            'link' => 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1 M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1',
            'lock' => 'M6 11h12v9H6v-9Z M8.5 11V8a3.5 3.5 0 0 1 7 0v3',
            'logout' => 'M10 7V5H5v14h5v-2 M10 12h9 M16 8l4 4-4 4',
            'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z M12 7v5l3 2',
            'check' => 'M5 12.5 10 17l9-10',
            'back' => 'M15 6l-6 6 6 6',
            'trash' => 'M4 7h16 M10 11v6 M14 11v6 M6 7l1 13h10l1-13 M9 7V4h6v3',
            'expand' => 'M4 9V4h5 M20 9V4h-5 M4 15v5h5 M20 15v5h-5',
            'home' => 'M4 11l8-7 8 7 M6 9.5V20h4.5v-5.5h3V20H18V9.5',
            'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z M22 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
        ];
    }

    function dartiqo_i(string $name, string $class = ''): string
    {
        return dartiqo_icon(dartiqo_icons()[$name] ?? '', $class);
    }

    function dartiqo_theme_toggle(string $class = 'icon-btn'): string
    {
        return '<button class="' . e($class) . '" type="button" data-theme aria-label="Přepnout světlý a tmavý režim" title="Přepnout světlý a tmavý režim">'
            . dartiqo_i('sun', 'light:hidden') . dartiqo_i('moon', 'hidden light:block')
            . '</button>';
    }

    /** @param list<float|int> $values */
    function dartiqo_spark(array $values, string $stroke = '#faff00', int $w = 120, int $h = 36): string
    {
        $count = count($values);
        if ($count < 2) {
            return '';
        }
        $min = min($values);
        $max = max($values);
        $span = $max - $min ?: 1;
        $points = [];
        foreach (array_values($values) as $i => $value) {
            $x = round($i / ($count - 1) * $w, 2);
            $y = round(($h - 3) - (($value - $min) / $span) * ($h - 6), 2);
            $points[] = $x . ',' . $y;
        }
        $line = 'M' . implode(' L', $points);
        $area = $line . ' L' . $w . ',' . $h . ' L0,' . $h . ' Z';
        $id = 'sp' . substr(md5($line . $stroke), 0, 8);
        [$lastX, $lastY] = explode(',', end($points));

        return '<svg class="block h-full w-full overflow-visible" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" aria-hidden="true">'
            . '<defs><linearGradient id="' . $id . '" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="' . e($stroke) . '" stop-opacity=".28"/><stop offset="1" stop-color="' . e($stroke) . '" stop-opacity="0"/></linearGradient></defs>'
            . '<path d="' . $area . '" fill="url(#' . $id . ')"/>'
            . '<path d="' . $line . '" fill="none" stroke="' . e($stroke) . '" stroke-width="1.75" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>'
            . '<circle cx="' . $lastX . '" cy="' . $lastY . '" r="2.5" fill="' . e($stroke) . '" vector-effect="non-scaling-stroke"/>'
            . '</svg>';
    }

    /**
     * @param list<float|int> $values
     * @param array{ref?: float|int|null, refLabel?: string|null, unit?: string, peak?: bool, grid?: int} $o
     */
    function dartiqo_line(array $values, string $color = '#faff00', array $o = []): string
    {
        $values = array_values($values);
        $count = count($values);
        if ($count < 2) {
            return '';
        }
        $ref = $o['ref'] ?? null;
        $unit = $o['unit'] ?? '';
        $min = min($ref !== null ? [...$values, $ref] : $values);
        $max = max($ref !== null ? [...$values, $ref] : $values);
        $span = ($max - $min) ?: 1;
        $toY = static fn (float $v): float => round(90 - ($v - $min) / $span * 76, 2);
        $pts = [];
        foreach ($values as $i => $value) {
            $pts[] = [round($i / ($count - 1) * 100, 2), $toY((float) $value)];
        }

        $line = 'M' . $pts[0][0] . ',' . $pts[0][1];
        for ($i = 0; $i < $count - 1; $i++) {
            [$x0, $y0] = $pts[max(0, $i - 1)];
            [$x1, $y1] = $pts[$i];
            [$x2, $y2] = $pts[$i + 1];
            [$x3, $y3] = $pts[min($count - 1, $i + 2)];
            $line .= ' C' . round($x1 + ($x2 - $x0) / 6, 2) . ',' . round($y1 + ($y2 - $y0) / 6, 2)
                . ' ' . round($x2 - ($x3 - $x1) / 6, 2) . ',' . round($y2 - ($y3 - $y1) / 6, 2)
                . ' ' . $x2 . ',' . $y2;
        }
        $area = $line . ' L100,100 L0,100 Z';
        $id = 'ln' . substr(md5($line . $color), 0, 8);
        $c = e($color);

        $svg = '<svg class="chart-reveal absolute inset-0 h-full w-full overflow-visible" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">'
            . '<defs><linearGradient id="' . $id . '" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="' . $c . '" stop-opacity=".32"/><stop offset=".7" stop-color="' . $c . '" stop-opacity=".06"/><stop offset="1" stop-color="' . $c . '" stop-opacity="0"/></linearGradient></defs>';
        for ($g = 1; $g <= ($o['grid'] ?? 0); $g++) {
            $gy = round(100 / (($o['grid'] ?? 0) + 1) * $g, 2);
            $svg .= '<line x1="0" x2="100" y1="' . $gy . '" y2="' . $gy . '" stroke="#ffffff0d" stroke-width="1" vector-effect="non-scaling-stroke"/>';
        }
        if ($ref !== null) {
            $ry = $toY((float) $ref);
            $svg .= '<line x1="0" x2="100" y1="' . $ry . '" y2="' . $ry . '" stroke="#ffffff40" stroke-width="1" stroke-dasharray="3 4" vector-effect="non-scaling-stroke"/>';
        }
        $svg .= '<path d="' . $area . '" fill="url(#' . $id . ')"/>'
            . '<path d="' . $line . '" fill="none" stroke="' . $c . '" stroke-opacity=".18" stroke-width="7" stroke-linecap="round" vector-effect="non-scaling-stroke"/>'
            . '<path d="' . $line . '" fill="none" stroke="' . $c . '" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>'
            . '</svg>';

        $html = '<div class="relative h-full w-full">' . $svg;
        if ($ref !== null && !empty($o['refLabel'])) {
            $html .= '<span class="chart-note pointer-events-none absolute right-0 translate-y-[3px] text-[10px] leading-none text-zinc-500" style="top:' . $toY((float) $ref) . '%;--d:640">' . e($o['refLabel']) . '</span>';
        }
        if (!empty($o['peak'])) {
            $peak = (int) array_search(max($values), $values, true);
            [$px, $py] = $pts[$peak];
            $peakAt = (int) round($peak / max(1, $count - 1) * 1000);
            $html .= '<span class="chart-dot pointer-events-none absolute size-2 -translate-1/2 rounded-full ring-2 ring-zinc-900" style="left:' . $px . '%;top:' . $py . '%;background:' . $c . ';--d:' . $peakAt . '"></span>'
                . '<span class="chart-note pointer-events-none absolute -translate-x-1/2 -translate-y-[calc(100%+8px)] rounded-md bg-white/10 px-1.5 py-0.5 text-[10px] leading-none font-semibold whitespace-nowrap text-zinc-200 backdrop-blur" style="left:' . min(92, max(8, $px)) . '%;top:' . $py . '%;--d:' . $peakAt . '">max ' . e($values[$peak] . $unit) . '</span>';
        }
        [$lx, $ly] = end($pts);
        $html .= '<span class="chart-dot pointer-events-none absolute size-2.5 -translate-1/2" style="left:' . $lx . '%;top:' . $ly . '%;--d:980">'
            . '<i class="absolute inset-0 rounded-full opacity-60 motion-safe:animate-ping" style="background:' . $c . '"></i>'
            . '<i class="absolute inset-0 rounded-full ring-2 ring-zinc-900" style="background:' . $c . ';box-shadow:0 0 12px ' . $c . '"></i></span>';

        $width = round(100 / ($count - 1), 2);
        foreach ($pts as $i => [$x, $y]) {
            $align = $x < 12 ? 'left-0' : ($x > 88 ? 'right-0' : 'left-1/2 -translate-x-1/2');
            $html .= '<span class="group/pt absolute inset-y-0 z-10 -translate-x-1/2" style="left:' . $x . '%;width:' . $width . '%">'
                . '<i class="pointer-events-none absolute inset-y-0 left-1/2 w-px bg-white/20 opacity-0 transition-opacity group-hover/pt:opacity-100"></i>'
                . '<i class="pointer-events-none absolute left-1/2 size-2 -translate-1/2 rounded-full opacity-0 ring-2 ring-zinc-900 transition-opacity group-hover/pt:opacity-100" style="top:' . $y . '%;background:' . $c . '"></i>'
                . '<b class="pointer-events-none absolute bottom-full mb-1.5 hidden rounded-md bg-zinc-100 px-1.5 py-0.5 text-[10px] font-semibold whitespace-nowrap text-zinc-950 group-hover/pt:block ' . $align . '">' . e($values[$i] . $unit) . '</b>'
                . '</span>';
        }

        return $html . '</div>';
    }

    /**
     * @param list<float|int> $values
     * @param array{ref?: float|int|null, unit?: string} $o
     */
    function dartiqo_bars(array $values, string $color = '#faff00', array $o = []): string
    {
        $values = array_values($values);
        $ref = $o['ref'] ?? null;
        $unit = $o['unit'] ?? '';
        $max = max([...$values, $ref ?? 0, 1]);
        $c = e($color);
        $last = count($values) - 1;

        $html = '<div class="relative flex h-full items-end gap-[3px]">';
        if ($ref !== null) {
            $html .= '<span class="pointer-events-none absolute inset-x-0 border-t border-dashed border-white/20" style="bottom:' . round($ref / $max * 100, 1) . '%" aria-hidden="true"></span>';
        }
        foreach ($values as $i => $value) {
            $isZero = (float) $value === 0.0;
            $height = $isZero ? 8 : max(12, round($value / $max * 100, 1));
            $style = $isZero
                ? 'height:' . $height . '%;background:#ffffff12;--o:1;--i:' . $i
                : 'height:' . $height . '%;background:' . $c . ';--o:' . ($i === $last ? 1 : round(.35 + .45 * $value / $max, 2)) . ';--i:' . $i . ($i === $last ? ';box-shadow:0 0 14px ' . $c : '');
            $align = $i < 2 ? 'left-0' : ($i > $last - 2 ? 'right-0' : 'left-1/2 -translate-x-1/2');
            $html .= '<span class="chart-bar group/bar relative flex-1 rounded-[4px] opacity-(--o) transition-opacity hover:opacity-100" style="' . $style . '">'
                . '<b class="pointer-events-none absolute bottom-full z-10 mb-1.5 hidden rounded-md bg-zinc-100 px-1.5 py-0.5 text-[10px] font-semibold whitespace-nowrap text-zinc-950 group-hover/bar:block ' . $align . '">' . e($value . $unit) . '</b>'
                . '</span>';
        }

        return $html . '</div>';
    }

    function dartiqo_ring(float $percent, string $color = '#faff00', string $inner = ''): string
    {
        $c = e($color);
        $p = max(0, min(100, $percent));

        return '<span class="relative grid size-12 shrink-0 place-items-center" aria-hidden="true">'
            . '<svg class="absolute inset-0 size-full -rotate-90" viewBox="0 0 36 36">'
            . '<circle cx="18" cy="18" r="15.915" fill="none" stroke="#ffffff12" stroke-width="3.2"/>'
            . '<circle class="chart-ring" cx="18" cy="18" r="15.915" fill="none" stroke="' . $c . '" stroke-width="3.2" stroke-linecap="round" style="--p:' . $p . ';filter:drop-shadow(0 0 3px ' . $c . ')"/>'
            . '</svg>'
            . ($inner !== '' ? '<small class="num relative text-[9px] font-semibold text-zinc-300">' . e($inner) . '</small>' : '')
            . '</span>';
    }

    /** @return list<float> */
    function dartiqo_series(int $days, float $mean, float $rise, float $seed = 0.0): array
    {
        $raw = [];
        for ($i = 0; $i < $days; $i++) {
            $t = $days === 1 ? 0.0 : ($i / ($days - 1) - 0.5);
            $raw[] = $mean + $rise * $t + sin($i * 0.9 + $seed) * 2.6 + cos($i * 0.45 + $seed) * 1.5;
        }
        $shift = $mean - (array_sum($raw) / max(1, $days));

        return array_map(static fn (float $value): float => round($value + $shift, 1), $raw);
    }

    function dartiqo_today_label(): string
    {
        $days = ['Neděle', 'Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota'];
        $months = ['ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince'];

        return $days[(int) date('w')] . ' ' . (int) date('j') . '. ' . $months[(int) date('n') - 1];
    }

    /** Celá stránka bez dat: nadpis a výzva odehrát první zápas v aplikaci. */
    function dartiqo_empty_page(string $eyebrow, string $title, string $heading, string $text, string $icon = 'target'): string
    {
        return '<main class="mx-auto w-full max-w-5xl">'
            . '<header class="mb-8"><p class="eyebrow">' . e($eyebrow) . '</p><h1 class="mt-2">' . e($title) . '<span class="text-accent">.</span></h1></header>'
            . dartiqo_empty_panel($heading, $text, $icon)
            . '</main>';
    }

    function dartiqo_empty_panel(string $heading, string $text, string $icon = 'target'): string
    {
        return '<section class="panel flex flex-col items-center gap-4 py-14! text-center">'
            . '<span class="grid size-16 place-items-center rounded-3xl bg-accent/10 text-accent ring-1 ring-accent/25 ring-inset [&_svg]:size-7">' . dartiqo_i($icon) . '</span>'
            . '<h2>' . e($heading) . '</h2>'
            . '<p class="max-w-md text-sm text-zinc-400">' . e($text) . '</p>'
            . '</section>';
    }
}
