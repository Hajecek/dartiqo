<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layouts.public'): void
    {
        $content = self::partial($template, $data);
        if ($layout !== null) {
            $content = self::partial($layout, array_merge($data, ['content' => $content]));
        }
        echo $content;
    }

    public static function partial(string $template, array $data = []): string
    {
        $path = dirname(__DIR__, 2) . '/resources/views/' . str_replace('.', '/', $template) . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('Šablona nebyla nalezena: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;

        return (string) ob_get_clean();
    }
}
