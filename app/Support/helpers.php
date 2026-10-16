<?php

declare(strict_types=1);

use App\Core\Application;

function app(): Application
{
    return Application::getInstance();
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
    return app()->url('/assets/' . ltrim($path, '/'));
}
