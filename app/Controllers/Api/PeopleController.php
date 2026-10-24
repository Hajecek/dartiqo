<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Repositories\FriendRepository;
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
        $userId = (int) $user['id'];
        $users = new UserRepository(db());
        $query = $request->input('q');
        $people = $users->directory($query, $userId);
        $relations = (new FriendRepository(db()))->relations($userId, array_column($people, 'id'));
        foreach ($people as $index => $person) {
            $people[$index]['relation'] = $relations[(int) $person['id']] ?? 'none';
        }
        $this->json([
            'ok' => true,
            'suggested' => !$users->isDirectorySearch($query),
            'people' => $people,
        ]);
    }
}
