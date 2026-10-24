<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\FcmTokenRepository;

/**
 * Odeslání push přes FCM HTTP v1. Stejný postup jako Privofit AppPushService:
 * účet služby, OAuth token a zpráva na každý aktivní token uživatele.
 */
final class PushService
{
    private static ?string $accessToken = null;

    private static int $accessTokenExp = 0;

    private readonly FcmTokenRepository $tokens;

    public function __construct(private readonly Database $db)
    {
        $this->tokens = new FcmTokenRepository($db);
    }

    public function send(int $userId, string $title, string $body, string $type): void
    {
        try {
            $this->deliverToUser($userId, $title, $body, $type);
        } catch (\Throwable $e) {
            error_log('Dartiqo push: ' . $e->getMessage());
        }
    }

    private function deliverToUser(int $userId, string $title, string $body, string $type): void
    {
        if ($userId < 1 || $title === '' || $body === '') {
            return;
        }
        $deviceTokens = $this->tokens->activeTokens($userId);
        if ($deviceTokens === []) {
            return;
        }
        $account = $this->firebaseAccount();
        if ($account === null) {
            return;
        }
        $accessToken = $this->accessToken($account);
        if ($accessToken === null) {
            return;
        }
        foreach ($deviceTokens as $deviceToken) {
            $this->postFcm($account, $accessToken, $deviceToken, $title, $body, $type);
        }
    }

    /** @return array{project_id:string,client_email:string,private_key:string}|null */
    private function firebaseAccount(): ?array
    {
        $path = trim((string) (getenv('FIREBASE_CREDENTIALS') ?: ''));
        if ($path === '') {
            $path = dirname(__DIR__, 2) . '/storage/firebase/dartiqo-firebase-adminsdk.json';
        } elseif ($path[0] !== '/') {
            $path = dirname(__DIR__, 2) . '/' . ltrim($path, '/');
        }
        if (!is_file($path)) {
            error_log('Dartiqo push: chybí Firebase účet služby (' . $path . ')');

            return null;
        }
        if (!is_readable($path)) {
            error_log('Dartiqo push: Firebase účet služby nejde přečíst (' . $path . ')');

            return null;
        }
        try {
            $data = json_decode((string) file_get_contents($path), true, 8, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            error_log('Dartiqo push: Firebase účet služby nejde načíst (' . $path . ')');

            return null;
        }
        if (!is_array($data)) {
            return null;
        }
        foreach (['project_id', 'client_email', 'private_key'] as $key) {
            if (!is_string($data[$key] ?? null) || $data[$key] === '') {
                return null;
            }
        }

        return [
            'project_id' => $data['project_id'],
            'client_email' => $data['client_email'],
            'private_key' => $data['private_key'],
        ];
    }

    /** @param array{project_id:string,client_email:string,private_key:string} $account */
    private function accessToken(array $account, bool $force = false): ?string
    {
        if (!$force && self::$accessToken !== null && self::$accessTokenExp > time() + 60) {
            return self::$accessToken;
        }
        $cache = $this->accessTokenCachePath();
        if (!$force && is_readable($cache)) {
            $stored = json_decode((string) file_get_contents($cache), true);
            if (is_array($stored) && is_string($stored['token'] ?? null) && (int) ($stored['exp'] ?? 0) > time() + 60) {
                self::$accessToken = $stored['token'];
                self::$accessTokenExp = (int) $stored['exp'];

                return self::$accessToken;
            }
        }
        $now = time();
        $unsigned = $this->b64url((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']))
            . '.'
            . $this->b64url((string) json_encode([
                'iss' => $account['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
        $key = openssl_pkey_get_private($account['private_key']);
        if ($key === false) {
            error_log('Dartiqo push: Firebase klíč nejde načíst');

            return null;
        }
        $signature = '';
        if (!openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
            error_log('Dartiqo push: Firebase klíč nejde podepsat');

            return null;
        }
        $jwt = $unsigned . '.' . $this->b64url($signature);
        $raw = $this->http('https://oauth2.googleapis.com/token', [
            'Content-Type: application/x-www-form-urlencoded',
        ], http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]), $status);
        $decoded = json_decode($raw, true);
        $token = is_array($decoded) ? (string) ($decoded['access_token'] ?? '') : '';
        if ($status >= 400 || $token === '') {
            error_log('Dartiqo push: Firebase přístupový token se nepodařilo získat (' . $status . ')');

            return null;
        }
        $exp = $now + max(120, (int) ($decoded['expires_in'] ?? 3600));
        self::$accessToken = $token;
        self::$accessTokenExp = $exp;
        $dir = dirname($cache);
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        file_put_contents($cache, json_encode(['token' => $token, 'exp' => $exp]));
        @chmod($cache, 0600);

        return $token;
    }

    private function forgetAccessToken(): void
    {
        self::$accessToken = null;
        self::$accessTokenExp = 0;
        $cache = $this->accessTokenCachePath();
        if (is_file($cache)) {
            unlink($cache);
        }
    }

    private function accessTokenCachePath(): string
    {
        return dirname(__DIR__, 2) . '/storage/cache/fcm-oauth.json';
    }

    /** @param array{project_id:string,client_email:string,private_key:string} $account */
    private function postFcm(
        array $account,
        string $accessToken,
        string $deviceToken,
        string $title,
        string $body,
        string $type,
    ): void {
        $status = $this->deliverFcm($account, $accessToken, $deviceToken, $title, $body, $type);
        if ($status !== 401) {
            return;
        }
        $this->forgetAccessToken();
        $fresh = $this->accessToken($account, true);
        if ($fresh === null) {
            return;
        }
        $this->deliverFcm($account, $fresh, $deviceToken, $title, $body, $type);
    }

    /** @param array{project_id:string,client_email:string,private_key:string} $account */
    private function deliverFcm(
        array $account,
        string $accessToken,
        string $deviceToken,
        string $title,
        string $body,
        string $type,
    ): int {
        $message = [
            'token' => $deviceToken,
            'data' => ['type' => $type],
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'android' => [
                'priority' => 'HIGH',
                'notification' => ['sound' => 'default'],
            ],
            'apns' => [
                'headers' => ['apns-priority' => '10'],
                'payload' => ['aps' => ['sound' => 'default']],
            ],
        ];
        $url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($account['project_id']) . '/messages:send';
        $raw = $this->http($url, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ], (string) json_encode(['message' => $message], JSON_UNESCAPED_UNICODE), $status);
        if ($status >= 400) {
            $errorCode = $this->fcmErrorCode($raw);
            error_log('Dartiqo push: FCM se nepodařilo odeslat (' . $status . ' ' . $errorCode . ')');
            if (in_array($errorCode, ['UNREGISTERED', 'NOT_FOUND'], true)) {
                $this->tokens->invalidate($deviceToken, $errorCode);
            }
        }

        return $status;
    }

    private function fcmErrorCode(string $raw): string
    {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return '';
        }
        $code = (string) ($decoded['error']['status'] ?? '');
        foreach ($decoded['error']['details'] ?? [] as $detail) {
            if (is_array($detail) && is_string($detail['errorCode'] ?? null) && $detail['errorCode'] !== '') {
                return $detail['errorCode'];
            }
        }

        return $code;
    }

    /** @param list<string> $headers */
    private function http(string $url, array $headers, string $body, ?int &$status): string
    {
        $status = 0;
        $ch = curl_init($url);
        if ($ch === false) {
            return '';
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 8,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return is_string($raw) ? $raw : '';
    }

    private function b64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
