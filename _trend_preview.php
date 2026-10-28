<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

App\Core\Application::create();
auth()->startSession();
$_SESSION['user_id'] = 1;
app()->redirect('/app/statistics');
