<?php

declare(strict_types=1);

// Bez toho json_encode podle php.ini v XAMPPu píše 61.23 jako 61.229999…
ini_set('serialize_precision', '-1');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/app/Support/helpers.php';
