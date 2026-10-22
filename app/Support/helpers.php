<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Auth;
use App\Core\Database;

function app(): Application
{
    return Application::getInstance();
}

function db(): Database
{
    return app()->db();
}

function auth(): Auth
{
    return app()->auth();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    return app()->url($path);
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = dirname(__DIR__, 2) . '/assets/' . $path;
    $version = is_file($file) ? '?v=' . filemtime($file) : '';

    return app()->url('/assets/' . $path) . $version;
}
