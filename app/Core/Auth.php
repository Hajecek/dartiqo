<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

final class Auth
{
    private const REMEMBER_COOKIE = 'dartiqo_remember';
    private const REMEMBER_DAYS = 30;

    /** @var array<string, mixed>|null|false false = ještě nenačteno */
    private array|null|false $user = false;

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('dartiqo_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $this->cookiePath(),
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        if ($this->user !== false) {
            return $this->user;
        }
        $this->startSession();

        $id = (int) ($_SESSION['user_id'] ?? 0);
        $user = $id > 0 ? $this->users->findById($id) : $this->userFromRememberCookie();
        if ($user !== null && $user['status'] !== 'active') {
            $this->logout();
            $user = null;
        }

        return $this->user = $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    /** Načte znovu řádek z DB (po úpravě profilu). */
    public function refreshUser(): void
    {
        $current = $this->user();
        if ($current === null) {
            return;
        }
        $this->user = $this->users->findById((int) $current['id']);
    }

    /** @return array<string, mixed>|null */
    public function attempt(string $identity, string $password, bool $remember): ?array
    {
        $user = $this->verifyCredentials($identity, $password);
        if ($user !== null) {
            $this->login($user, $remember);
        }

        return $user;
    }

    /**
     * Ověří heslo bez přihlášení do session (mobilní API).
     *
     * @return array<string, mixed>|null
     */
    public function verifyCredentials(string $identity, string $password): ?array
    {
        $user = $this->users->findByLogin($identity);
        // Ověření i pro neexistující účet, aby délka odpovědi neprozrazovala registrované e-maily
        $hash = $user['password_hash'] ?? '$2y$10$cfTFYdLxJUiEHUjxUx5X5OQflWLcI1W39ADvTg6Be.EqDGD.Xl5z2';
        if (!password_verify($password, $hash) || $user === null || $user['status'] !== 'active') {
            return null;
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePassword((int) $user['id'], $password);
        }

        return $user;
    }

    /** @param array<string, mixed> $user */
    public function login(array $user, bool $remember = false): void
    {
        $this->startSession();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $this->users->touchLogin((int) $user['id']);
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $this->users->setRememberToken((int) $user['id'], hash('sha256', $token));
            $this->setRememberCookie($user['id'] . ':' . $token, time() + self::REMEMBER_DAYS * 86400);
        }
        $this->user = $user;
    }

    public function logout(): void
    {
        $this->startSession();
        $id = (int) ($_SESSION['user_id'] ?? 0);
        if ($id > 0) {
            $this->users->setRememberToken($id, null);
        }
        $_SESSION = [];
        session_regenerate_id(true);
        $this->setRememberCookie('', time() - 3600);
        $this->user = null;
    }

    public function csrfToken(): string
    {
        $this->startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function verifyCsrf(string $token): bool
    {
        return $token !== '' && hash_equals($this->csrfToken(), $token);
    }

    /** @return array<string, mixed>|null */
    private function userFromRememberCookie(): ?array
    {
        $cookie = (string) ($_COOKIE[self::REMEMBER_COOKIE] ?? '');
        if (!preg_match('/^(\d+):([a-f0-9]{64})$/', $cookie, $m)) {
            return null;
        }
        $user = $this->users->findById((int) $m[1]);
        $stored = (string) ($user['remember_token_hash'] ?? '');
        if ($user === null || $stored === '' || !hash_equals($stored, hash('sha256', $m[2]))) {
            $this->setRememberCookie('', time() - 3600);

            return null;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        return $user;
    }

    private function setRememberCookie(string $value, int $expires): void
    {
        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires' => $expires,
            'path' => $this->cookiePath(),
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function cookiePath(): string
    {
        return rtrim(app()->url('/'), '/') . '/';
    }

    private function isHttps(): bool
    {
        return ($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off';
    }
}
