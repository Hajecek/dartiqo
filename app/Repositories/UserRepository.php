<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class UserRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public function findByLogin(string $identity): ?array
    {
        $column = str_contains($identity, '@') ? 'email' : 'username';

        return $this->db->fetch("SELECT * FROM users WHERE {$column} = ?", [$identity]);
    }

    public function usernameExists(string $username): bool
    {
        return $this->db->fetch('SELECT 1 FROM users WHERE username = ?', [$username]) !== null;
    }

    public function emailExists(string $email): bool
    {
        return $this->db->fetch('SELECT 1 FROM users WHERE email = ?', [$email]) !== null;
    }

    /** @param array{username:string,email:string,password:string,first_name:string,last_name:string,avatar_path?:?string} $data */
    public function create(array $data): int
    {
        return $this->db->insert('users', [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'avatar_path' => $data['avatar_path'] ?? null,
        ]);
    }

    public function updatePassword(int $id, string $password): void
    {
        $this->db->update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $id]);
    }

    public function updateAvatar(int $id, ?string $path): void
    {
        $this->db->update('users', ['avatar_path' => $path], 'id = :id', ['id' => $id]);
    }

    public function setRememberToken(int $id, ?string $hash): void
    {
        $this->db->update('users', ['remember_token_hash' => $hash], 'id = :id', ['id' => $id]);
    }

    public function touchLogin(int $id): void
    {
        $this->db->query('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = ?', [$id]);
    }

    /**
     * Data bezpečná pro odeslání do prohlížeče.
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public static function toPublic(array $user): array
    {
        $plan = (string) $user['plan'];
        $expires = $user['plan_expires_at'] ?? null;
        if ($plan !== 'free' && $expires !== null && strtotime((string) $expires . ' UTC') < time()) {
            $plan = 'free';
        }

        return [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first' => $user['first_name'],
            'last' => $user['last_name'],
            'photo' => $user['avatar_path'] ? '/' . $user['avatar_path'] : null,
            'plan' => $plan,
            'planExpiresAt' => $expires,
            'role' => $user['role'],
        ];
    }
}
