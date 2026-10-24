<?php

declare(strict_types=1);

use App\Core\Application;
use App\Services\PushService;

/**
 * Ruční push na všechna aktivní zařízení uživatele.
 * php scripts/send-push.php <userId> <nadpis> <text>
 */
require dirname(__DIR__) . '/bootstrap.php';

$userId = (int) ($argv[1] ?? 0);
$title = trim((string) ($argv[2] ?? ''));
$body = trim((string) ($argv[3] ?? ''));
if ($userId < 1 || $title === '' || $body === '') {
    fwrite(STDERR, "Použití: php scripts/send-push.php <userId> <nadpis> <text>\n");
    exit(1);
}

Application::create();
(new PushService(db()))->send($userId, $title, $body, 'manual');
fwrite(STDOUT, "Požadavek na push pro uživatele {$userId} je odeslaný. Když nedorazí, koukni do PHP error logu.\n");
