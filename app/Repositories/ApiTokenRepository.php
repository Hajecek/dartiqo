<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ApiTokenRepository
{
    private const LIFETIME_DAYS = 365;

    public function __construct(private readonly Database $db)
    {
    }

    /** Vrátí nový token. V databázi zůstane jen jeho SHA-256. */
    public function create(int $userId, string $name): string
    {
        $token = bin2hex(random_bytes(32));
        $this->db->insert('api_tokens', [
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'name' => mb_substr($name !== '' ? $name : 'Mobilní aplikace', 0, 100),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + self::LIFETIME_DAYS * 86400),
        ]);

        return $token;
    }

    /** @return array<string, mixed>|null */
    public function findUser(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $row = $this->db->fetch(
            'SELECT u.*, t.id AS token_id, t.last_used_at AS token_last_used_at
             FROM api_tokens t JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND t.expires_at > UTC_TIMESTAMP() AND u.status = \'active\'',
            [hash('sha256', $token)]
        );
        if ($row === null) {
            return null;
        }
        // Zápis při každé šipce by zbytečně zatěžoval databázi
        $lastUsed = $row['token_last_used_at'] !== null ? strtotime($row['token_last_used_at'] . ' UTC') : 0;
        if ($lastUsed < time() - 300) {
            $this->db->query('UPDATE api_tokens SET last_used_at = UTC_TIMESTAMP() WHERE id = ?', [(int) $row['token_id']]);
        }

        return $row;
    }

    public function revoke(string $token): void
    {
        $this->db->query('DELETE FROM api_tokens WHERE token_hash = ?', [hash('sha256', $token)]);
    }
}
