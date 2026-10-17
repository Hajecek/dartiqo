<?php

declare(strict_types=1);

use App\Controllers\Web\PageController;

$router = app()->router();

$router->get('/', [PageController::class, 'home']);
$router->get('/game-modes', [PageController::class, 'gameModes']);
$router->get('/herni-mody', [PageController::class, 'gameModes']);
$router->get('/game-modes/{mode}', [PageController::class, 'mode']);
$router->get('/training', [PageController::class, 'training']);
$router->get('/career', [PageController::class, 'career']);
$router->get('/online', [PageController::class, 'online']);
$router->get('/statistics', [PageController::class, 'statistics']);
$router->get('/about', [PageController::class, 'about']);
$router->get('/features', [PageController::class, 'features']);
$router->get('/funkce', [PageController::class, 'features']);
$router->get('/contact', [PageController::class, 'contact']);
$router->get('/leaderboards', [PageController::class, 'leaderboards']);
$router->get('/tournaments', [PageController::class, 'tournaments']);
$router->get('/app', [PageController::class, 'dashboard']);
$router->get('/app/statistics', [PageController::class, 'appStatistics']);
$router->get('/app/games/{id}', [PageController::class, 'appGames']);
$router->get('/app/games', [PageController::class, 'appGames']);
$router->get('/app/profile', [PageController::class, 'profile']);
$router->get('/app/settings', [PageController::class, 'settings']);
$router->get('/login', [PageController::class, 'login']);
$router->get('/prihlaseni', [PageController::class, 'login']);
$router->get('/register', [PageController::class, 'register']);
$router->get('/registrace', [PageController::class, 'register']);
$router->get('/forgot-password', [PageController::class, 'forgotPassword']);
$router->get('/reset-password', [PageController::class, 'resetPassword']);
