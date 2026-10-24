<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDOException;

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

    public function usernameTaken(string $username, int $exceptId): bool
    {
        return $this->db->fetch('SELECT 1 FROM users WHERE username = ? AND id != ?', [$username, $exceptId]) !== null;
    }

    public function emailExists(string $email): bool
    {
        return $this->db->fetch('SELECT 1 FROM users WHERE email = ?', [$email]) !== null;
    }

    public function emailTaken(string $email, int $exceptId): bool
    {
        return $this->db->fetch('SELECT 1 FROM users WHERE email = ? AND id != ?', [$email, $exceptId]) !== null;
    }

    /** Prázdný nebo příliš krátký dotaz znamená návrhy, ne hledání. */
    public function isDirectorySearch(string $query): bool
    {
        return mb_strlen($this->needle($query)) >= 2;
    }

    /**
     * Návrhy bez dotazu, jinak hledání. E-mail ve výsledku není.
     *
     * @return list<array<string, mixed>>
     */
    public function directory(string $query, int $exceptId): array
    {
        return $this->isDirectorySearch($query)
            ? $this->searchDirectory($query, $exceptId)
            : $this->suggestDirectory($exceptId);
    }

    /**
     * Pár nejnovějších aktivních účtů, ať má hledání co ukázat ještě před psaním.
     *
     * @return list<array<string, mixed>>
     */
    public function suggestDirectory(int $exceptId, int $limit = 8): array
    {
        $limit = max(1, min($limit, 20));
        $rows = $this->db->fetchAll(
            'SELECT id, public_id, username, first_name, last_name, avatar_path, profile_revision
             FROM users
             WHERE status = \'active\' AND id != ?
             ORDER BY created_at DESC, id DESC
             LIMIT ' . $limit,
            [$exceptId]
        );

        return array_map(self::toDirectory(...), $rows);
    }

    /**
     * Aktivní účty podle jména, username, nebo přesného e-mailu. E-mail ve výsledku není.
     *
     * @return list<array<string, mixed>>
     */
    public function searchDirectory(string $query, int $exceptId): array
    {
        $needle = $this->needle($query);
        if (mb_strlen($needle) < 2) {
            return [];
        }

        $escaped = addcslashes($needle, '%_\\');
        $like = '%' . $escaped . '%';
        $prefix = $escaped . '%';
        $email = filter_var($needle, FILTER_VALIDATE_EMAIL) ? mb_strtolower($needle) : null;
        $emailClause = $email !== null ? ' OR email = ?' : '';
        $params = [$exceptId, $like, $like, $like, $like];
        if ($email !== null) {
            $params[] = $email;
        }
        $params[] = $needle;
        $params[] = $prefix;
        $rows = $this->db->fetchAll(
            'SELECT id, public_id, username, first_name, last_name, avatar_path, profile_revision
             FROM users
             WHERE status = \'active\' AND id != ?
               AND (username LIKE ? OR first_name LIKE ? OR last_name LIKE ?
                    OR CONCAT(first_name, \' \', last_name) LIKE ?' . $emailClause . ')
             ORDER BY CASE WHEN username = ? THEN 0 WHEN username LIKE ? THEN 1 ELSE 2 END, username
             LIMIT 20',
            $params
        );

        return array_map(self::toDirectory(...), $rows);
    }

    private function needle(string $query): string
    {
        $needle = trim($query);
        if (str_starts_with($needle, '@') && !str_contains(substr($needle, 1), '@')) {
            $needle = trim(substr($needle, 1));
        }

        return mb_substr($needle, 0, 40);
    }

    /** @param array{username:string,email:string,password:string,first_name:string,last_name:string,avatar_path?:?string} $data */
    public function create(array $data): int
    {
        for ($attempt = 0; ; $attempt++) {
            try {
                return $this->db->insert('users', [
                    'public_id' => self::newPublicId(),
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'avatar_path' => $data['avatar_path'] ?? null,
                ]);
            } catch (PDOException $e) {
                // 23000 = porušený unikátní klíč; při shodě public_id zkusí jiné
                if ($e->getCode() !== '23000' || !str_contains($e->getMessage(), 'users_public_unique') || $attempt >= 2) {
                    throw $e;
                }
            }
        }
    }

    public static function newPublicId(): string
    {
        return bin2hex(random_bytes(12));
    }

    public function updatePassword(int $id, string $password): void
    {
        $this->db->update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $id]);
    }

    public function updateAvatar(int $id, ?string $path): void
    {
        $this->db->update('users', ['avatar_path' => $path], 'id = :id', ['id' => $id]);
    }

    /**
     * @param array{first_name:string,last_name:string,username:string,email:string,avatar_path:?string} $data
     */
    public function updateProfile(int $id, array $data): void
    {
        $this->db->query(
            'UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, avatar_path = ?, profile_revision = profile_revision + 1, updated_at = UTC_TIMESTAMP() WHERE id = ?',
            [
                $data['first_name'],
                $data['last_name'],
                $data['username'],
                $data['email'],
                $data['avatar_path'],
                $id,
            ]
        );
    }

    public function setVisibility(int $id, string $visibility): void
    {
        if ($visibility !== 'public' && $visibility !== 'private') {
            throw new \InvalidArgumentException('Vyber veřejný, nebo soukromý účet.');
        }
        $this->db->query(
            'UPDATE users SET visibility = ?, profile_revision = profile_revision + 1, updated_at = UTC_TIMESTAMP() WHERE id = ?',
            [$visibility, $id]
        );
    }

    public function profileRevision(int $id): int
    {
        $row = $this->db->fetch('SELECT profile_revision FROM users WHERE id = ?', [$id]);

        return (int) ($row['profile_revision'] ?? 1);
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

        $revision = (int) ($user['profile_revision'] ?? 1);

        return [
            'id' => (int) $user['id'],
            'publicId' => (string) ($user['public_id'] ?? ''),
            'username' => $user['username'],
            'email' => $user['email'],
            'first' => $user['first_name'],
            'last' => $user['last_name'],
            'photo' => $user['avatar_path'] ? '/' . $user['avatar_path'] . '?v=' . $revision : null,
            'profileRevision' => $revision,
            'plan' => $plan,
            'planExpiresAt' => $expires,
            'role' => $user['role'],
            'visibility' => ($user['visibility'] ?? 'public') === 'private' ? 'private' : 'public',
        ];
    }

    /**
     * Veřejná kartička pro hledání hráčů. Bez e-mailu a dalších údajů účtu.
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public static function toDirectory(array $user): array
    {
        $revision = (int) ($user['profile_revision'] ?? 1);

        return [
            'id' => (int) $user['id'],
            'publicId' => (string) ($user['public_id'] ?? ''),
            'username' => $user['username'],
            'first' => $user['first_name'],
            'last' => $user['last_name'],
            'photo' => $user['avatar_path'] ? '/' . $user['avatar_path'] . '?v=' . $revision : null,
        ];
    }
}
