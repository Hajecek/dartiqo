<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Uložení FCM tokenu stejně jako Privofit: jeden aktivní token na přihlášení, hash je unikátní. */
final class FcmTokenRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function register(int $userId, int $apiTokenId, string $token, string $environment): void
    {
        $token = mb_substr($token, 0, 512);
        $hash = hash('sha256', $token);
        $now = gmdate('Y-m-d H:i:s');
        $this->db->query(
            'UPDATE fcm_tokens
             SET is_active = 0, invalidated_at = ?, invalid_reason = ?
             WHERE api_token_id = ? AND is_active = 1 AND token_hash <> ?',
            [$now, 'replaced', $apiTokenId, $hash]
        );
        $this->db->query(
            'INSERT INTO fcm_tokens (user_id, api_token_id, token, token_hash, environment, is_active, last_used_at)
             VALUES (?, ?, ?, ?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE
                token = VALUES(token),
                user_id = VALUES(user_id),
                api_token_id = VALUES(api_token_id),
                environment = VALUES(environment),
                is_active = 1,
                invalidated_at = NULL,
                invalid_reason = NULL,
                last_used_at = VALUES(last_used_at)',
            [$userId, $apiTokenId, $token, $hash, $environment, $now]
        );
    }

    /** @return list<string> */
    public function activeTokens(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT token FROM fcm_tokens
             WHERE user_id = ? AND is_active = 1 AND invalidated_at IS NULL AND token LIKE '%:%'",
            [$userId]
        );
        $tokens = [];
        foreach ($rows as $row) {
            $token = trim((string) ($row['token'] ?? ''));
            if ($token !== '' && str_contains($token, ':')) {
                $tokens[$token] = $token;
            }
        }

        return array_values($tokens);
    }

    public function invalidate(string $token, string $reason): void
    {
        $this->db->query(
            'UPDATE fcm_tokens
             SET is_active = 0, invalidated_at = UTC_TIMESTAMP(), invalid_reason = ?
             WHERE token_hash = ? AND is_active = 1',
            [mb_substr($reason, 0, 255), hash('sha256', $token)]
        );
    }

    public function deactivateForApiToken(int $apiTokenId, string $reason): void
    {
        $this->db->query(
            'UPDATE fcm_tokens
             SET is_active = 0, invalidated_at = UTC_TIMESTAMP(), invalid_reason = ?
             WHERE api_token_id = ? AND is_active = 1',
            [mb_substr($reason, 0, 255), $apiTokenId]
        );
    }
}
