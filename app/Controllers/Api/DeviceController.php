<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Repositories\FcmTokenRepository;

final class DeviceController extends ApiController
{
    /** @param array<string, string> $params */
    public function push(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $token = trim($request->input('token'));
        if ($token === '' || strlen($token) > 512) {
            $this->json(['ok' => false, 'message' => 'Chybí platný token.'], 422);
        }
        $environment = strtolower(trim($request->input('environment', 'production')));
        if (!in_array($environment, ['development', 'production'], true)) {
            $environment = 'production';
        }

        (new FcmTokenRepository(db()))->register(
            (int) $user['id'],
            (int) $user['token_id'],
            $token,
            $environment
        );
        $this->json(['ok' => true]);
    }
}
