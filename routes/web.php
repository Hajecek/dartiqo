<?php

declare(strict_types=1);

use App\Controllers\Api\AuthController as ApiAuthController;
use App\Controllers\Api\MatchController;
use App\Controllers\Api\LiveMatchController;
use App\Controllers\Web\AuthController;
use App\Controllers\Web\GameController;
use App\Controllers\Web\LiveController;
use App\Controllers\Web\PageController;
use App\Controllers\Web\ProfileController;

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
$router->post('/app/games/{id}/delete', [GameController::class, 'destroy']);
$router->get('/app/games', [PageController::class, 'appGames']);
$router->get('/app/career', [PageController::class, 'appCareer']);
$router->get('/app/live', [PageController::class, 'live']);
$router->get('/app/live/stream', [LiveController::class, 'stream']);
$router->get('/app/profile', [PageController::class, 'profile']);
$router->post('/app/profile', [ProfileController::class, 'update']);
$router->get('/app/profile/stream', [ProfileController::class, 'stream']);
$router->get('/app/settings', [PageController::class, 'settings']);
$router->get('/login', [PageController::class, 'login']);
$router->get('/prihlaseni', [PageController::class, 'login']);
$router->get('/register', [PageController::class, 'register']);
$router->get('/registrace', [PageController::class, 'register']);
$router->get('/forgot-password', [PageController::class, 'forgotPassword']);
$router->get('/reset-password', [PageController::class, 'resetPassword']);

$router->post('/register', [AuthController::class, 'register']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/register/availability', [AuthController::class, 'availability']);

$router->post('/api/v1/auth/login', [ApiAuthController::class, 'login']);
$router->post('/api/v1/auth/logout', [ApiAuthController::class, 'logout']);
$router->post('/api/v1/auth/social/{provider}', [ApiAuthController::class, 'social']);
$router->get('/api/v1/me', [ApiAuthController::class, 'me']);
$router->post('/api/v1/me', [ApiAuthController::class, 'update']);
$router->get('/api/v1/me/stream', [ApiAuthController::class, 'stream']);
$router->post('/api/v1/live-matches/{id}', [LiveMatchController::class, 'update']);
$router->get('/api/v1/matches', [MatchController::class, 'index']);
$router->post('/api/v1/matches/{id}', [MatchController::class, 'store']);
$router->post('/api/v1/matches/{id}/delete', [MatchController::class, 'destroy']);
