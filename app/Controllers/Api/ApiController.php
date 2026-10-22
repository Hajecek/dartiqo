<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Repositories\ApiTokenRepository;

/** Mobilní API. Přihlášení jen tokenem v hlavičce Authorization, nikdy cookie. */
abstract class ApiController
{
    protected ApiTokenRepository $tokens;

    public function __construct()
    {
        $this->tokens = new ApiTokenRepository(db());
    }

    /** @return array<string, mixed> */
    protected function requireUser(Request $request): array
    {
        $user = $this->tokens->findUser($request->bearerToken());
        if ($user === null) {
            $this->json(['ok' => false, 'message' => 'Přihlášení vypršelo. Přihlas se v aplikaci znovu.'], 401);
        }

        return $user;
    }

    /** @param array<string, mixed> $payload */
    protected function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
