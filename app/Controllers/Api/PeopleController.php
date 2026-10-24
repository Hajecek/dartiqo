<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Repositories\UserRepository;

final class PeopleController extends ApiController
{
    /**
     * Hledání registrovaných hráčů. Aplikace z výsledku umí uložit kamaráda.
     *
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $user = $this->requireUser($request);
        $people = (new UserRepository(db()))->searchDirectory($request->input('q'), (int) $user['id']);
        $this->json(['ok' => true, 'people' => $people]);
    }
}
