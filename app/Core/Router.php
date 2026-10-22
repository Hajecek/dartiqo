<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var list<array{method:string,pattern:string,handler:array|callable}> */
    private array $routes = [];

    public function get(string $path, array|callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array|callable $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $path,
            'handler' => $handler,
        ];
    }

    /** @return array{handler:array|callable,params:array<string,string>}|null */
    public function dispatch(Request $request): ?array
    {
        $method = $request->method() === 'HEAD' ? 'GET' : $request->method();
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $params = $this->match($route['pattern'], $request->path());
            if ($params === null) {
                continue;
            }
            return [
                'handler' => $route['handler'],
                'params' => $params,
            ];
        }

        return null;
    }

    /** @return array<string,string>|null */
    private function match(string $pattern, string $path): ?array
    {
        $pattern = rtrim($pattern, '/') ?: '/';
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', '(?P<$1>[^/]+)', $pattern) ?? $pattern;
        if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
            return null;
        }

        return array_filter($matches, static fn ($key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
    }
}
