<?php

if (!function_exists('dartiqo_board_shape')) {
    /** @return array<int, array{0:int,1:int,2:int}> */
    function dartiqo_board_shape(): array
    {
        return [
            20 => [150, 18, 40], 1 => [12, 2, 2], 18 => [18, 3, 4], 4 => [8, 1, 1],
            13 => [9, 1, 2], 6 => [10, 2, 2], 10 => [14, 2, 3], 15 => [7, 1, 1],
            2 => [9, 2, 1], 17 => [8, 1, 1], 3 => [16, 2, 4], 19 => [13, 2, 3],
            7 => [6, 1, 1], 16 => [7, 1, 1], 8 => [5, 1, 1], 11 => [4, 0, 1],
            14 => [5, 1, 1], 9 => [3, 0, 0], 12 => [0, 0, 0], 5 => [6, 1, 1],
        ];
    }

    /**
     * @param array<int, array{0:int,1:int,2:int}> $overrides
     * @param array{0:int,1:int} $bull
     * @return array{segments: array<int, array{0:int,1:int,2:int}>, bull: array{0:int,1:int}}
     */
    function dartiqo_scale_board(float $factor, array $overrides = [], array $bull = [1, 1]): array
    {
        $segments = [];
        foreach (dartiqo_board_shape() as $number => $parts) {
            $segments[$number] = $overrides[$number] ?? array_map(
                static fn (int $count): int => (int) round($count * $factor),
                $parts
            );
        }

        return ['segments' => $segments, 'bull' => $bull];
    }
}
