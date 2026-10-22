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
            'moon' => 'M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5Z',
            'bell' => 'M6 16V11a6 6 0 1 1 12 0v5l2 2H4l2-2Z M10 20a2 2 0 0 0 4 0',
            'mail' => 'M4 6h16v12H4V6Z M4 7l8 6 8-6',
            'link' => 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1 M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1',
            'lock' => 'M6 11h12v9H6v-9Z M8.5 11V8a3.5 3.5 0 0 1 7 0v3',
            'logout' => 'M10 7V5H5v14h5v-2 M10 12h9 M16 8l4 4-4 4',
            'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z M12 7v5l3 2',
            'check' => 'M5 12.5 10 17l9-10',
            'back' => 'M15 6l-6 6 6 6',
        ];
    }

    function dartiqo_i(string $name, string $class = ''): string
    {
        return dartiqo_icon(dartiqo_icons()[$name] ?? '', $class);
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
}
