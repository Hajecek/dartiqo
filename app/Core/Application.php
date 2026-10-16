<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Application
{
    private static ?self $instance = null;
    private Router $router;
    private Request $request;

    /** @var array<string, mixed> */
    private array $config = [];

    public static function create(): self
    {
        $app = new self();
        self::$instance = $app;
        $app->boot();

        return $app;
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            throw new \RuntimeException('Aplikace není inicializována.');
        }

        return self::$instance;
    }

    private function boot(): void
    {
        $root = dirname(__DIR__, 2);
        $this->config = [
            'app' => require $root . '/config/app.php',
        ];
        $this->router = new Router();
        $this->request = new Request();
    }

    public function run(): void
    {
        try {
            require dirname(__DIR__, 2) . '/routes/web.php';
            $match = $this->router->dispatch($this->request);
            if ($match === null) {
                $this->abort(404);
            }
            $handler = $match['handler'];
            if (is_array($handler)) {
                [$class, $method] = $handler;
                $controller = new $class();
                $controller->{$method}($this->request, $match['params']);
                return;
            }
            $handler($this->request, $match['params']);
        } catch (Throwable $e) {
            $debug = (bool) ($this->config['app']['debug'] ?? false);
            $this->abort(500, $debug ? $e->getMessage() : 'Došlo k neočekávané chybě.');
        }
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function url(string $path = '/'): string
    {
        $base = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
        $base = rtrim($base, '/');
        if ($base === '/') {
            $base = '';
        }
        $hash = '';
        $hashPos = strpos($path, '#');
        if ($hashPos !== false) {
            $hash = substr($path, $hashPos);
            $path = substr($path, 0, $hashPos);
        }
        if ($path === '' || $path === '/') {
            return $base . '/' . $hash;
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return $base . $path . $hash;
    }

    public function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        if ($status === 404 && !headers_sent()) {
            View::render('errors.404', [
                'title' => 'Nenalezeno | Dartiqo',
                'bodyClass' => '',
            ]);
            exit;
        }
        echo $message !== '' ? e($message) : 'Chyba';
        exit;
    }
}
