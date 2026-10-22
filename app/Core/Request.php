<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $uri = is_string($uri) && $uri !== '' ? $uri : '/';
        $base = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
        $base = rtrim($base, '/');
        if ($base !== '' && $base !== '/' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }
        $uri = '/' . trim($uri, '/');

        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return (string) ($_SERVER[$key] ?? '');
    }

    public function bearerToken(): string
    {
        // Apache s mod_php hlavičku Authorization do $_SERVER často nepředá
        $header = $this->header('Authorization') ?: (string) ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = array_change_key_case(apache_request_headers());
            $header = (string) ($headers['authorization'] ?? '');
        }

        return preg_match('/^Bearer\s+(\S+)$/i', trim($header), $m) ? $m[1] : '';
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        if (str_contains($this->header('Content-Type') ?: (string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
            $data = json_decode((string) file_get_contents('php://input'), true);

            return (is_array($data) ? $data : []) + $_GET;
        }

        return $_POST + $_GET;
    }

    public function input(string $key, string $default = ''): string
    {
        $value = $this->all()[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }
}
