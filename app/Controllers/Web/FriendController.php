<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\View;
use App\Repositories\FriendRepository;
use App\Repositories\UserRepository;
use App\Services\FriendDossier;
use InvalidArgumentException;

/** Kamarádi a žádosti na webu. Potvrzení je stejné jako v aplikaci. */
final class FriendController
{
    /** @param array<string, string> $params */
    public function index(Request $request, array $params = []): void
    {
        $user = $this->requireUser();
        $repo = new FriendRepository(db());
        $book = (new FriendDossier(db()))->withSummaries((int) $user['id'], $repo->book((int) $user['id']));
        $folder = $request->input('folder');
        if ($folder !== 'friends' && $folder !== 'requests') {
            $folder = $book['incoming'] !== [] ? 'requests' : 'friends';
        }
        $query = trim($request->input('q'));
        $people = $query === '' ? [] : (new UserRepository(db()))->searchDirectory($query, (int) $user['id']);
        $relations = $repo->relations((int) $user['id'], array_column($people, 'id'));
        foreach ($people as $index => $person) {
            $people[$index]['relation'] = $relations[(int) $person['id']] ?? 'none';
        }

        View::render('public.app-friends', [
            'title' => 'Kamarádi | Dartiqo',
            'bodyClass' => 'app-body',
            'book' => $book,
            'folder' => $folder,
            'query' => $query,
            'people' => $people,
            'flash' => $this->pullFlash(),
        ], 'layouts.app');
    }

    /** @param array<string, string> $params */
    public function show(Request $request, array $params = []): void
    {
        $user = $this->requireUser();
        try {
            $friend = (new FriendRepository(db()))->accepted((int) $user['id'], (int) ($params['id'] ?? 0));
        } catch (InvalidArgumentException $e) {
            $this->flash($e->getMessage());
            $this->back('friends');
        }

        $name = trim((string) $friend['user']['first'] . ' ' . (string) $friend['user']['last']);
        View::render('public.app-friend', [
            'title' => ($name !== '' ? $name : (string) $friend['user']['username']) . ' | Dartiqo',
            'bodyClass' => 'app-body',
            'friend' => $friend,
            'record' => (new FriendDossier(db()))->detail((int) $user['id'], (int) $friend['user']['id']),
            'flash' => $this->pullFlash(),
        ], 'layouts.app');
    }

    /** @param array<string, string> $params */
    public function store(Request $request, array $params = []): void
    {
        $user = $this->requireUser();
        $this->requireCsrf($request);
        $otherId = (int) $request->input('userId');
        try {
            $result = (new FriendRepository(db()))->request((int) $user['id'], $otherId);
            $this->flash($result['relation'] === 'accepted'
                ? 'Je z vás kamarádi.'
                : 'Žádost je odeslaná. Kamarádem bude, až ji přijme.');
        } catch (InvalidArgumentException $e) {
            $this->flash($e->getMessage());
        }
        $this->back('friends', $request->input('q'));
    }

    /** @param array<string, string> $params */
    public function accept(Request $request, array $params = []): void
    {
        $this->act($request, $params, 'accept', 'requests', 'Žádost je přijatá.');
    }

    /** @param array<string, string> $params */
    public function decline(Request $request, array $params = []): void
    {
        $this->act($request, $params, 'decline', 'requests', 'Žádost je odmítnutá.');
    }

    /** @param array<string, string> $params */
    public function cancel(Request $request, array $params = []): void
    {
        $this->act($request, $params, 'cancel', 'requests', 'Žádost je zrušená.');
    }

    /** @param array<string, string> $params */
    public function destroy(Request $request, array $params = []): void
    {
        $this->act($request, $params, 'remove', 'friends', 'Kamarád je odebraný.');
    }

    /** @param array<string, string> $params */
    private function act(Request $request, array $params, string $action, string $folder, string $success): void
    {
        $user = $this->requireUser();
        $this->requireCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        $repo = new FriendRepository(db());
        try {
            match ($action) {
                'accept' => $repo->accept((int) $user['id'], $id),
                'decline' => $repo->decline((int) $user['id'], $id),
                'cancel' => $repo->cancel((int) $user['id'], $id),
                default => $repo->remove((int) $user['id'], $id),
            };
            $this->flash($success);
        } catch (InvalidArgumentException $e) {
            $this->flash($e->getMessage());
        }
        $this->back($folder);
    }

    /** @return array<string, mixed> */
    private function requireUser(): array
    {
        $user = auth()->user();
        if ($user === null) {
            app()->redirect('/login');
        }

        return $user;
    }

    private function requireCsrf(Request $request): void
    {
        $token = $request->header('X-CSRF-Token') ?: $request->input('_csrf');
        if (!auth()->verifyCsrf($token)) {
            $this->flash('Platnost formuláře vypršela. Obnov stránku a zkus to znovu.');
            $this->back($request->input('folder') === 'requests' ? 'requests' : 'friends');
        }
    }

    private function flash(string $message): void
    {
        auth()->csrfToken();
        $_SESSION['friends_flash'] = $message;
    }

    private function pullFlash(): ?string
    {
        auth()->csrfToken();
        $message = $_SESSION['friends_flash'] ?? null;
        unset($_SESSION['friends_flash']);

        return is_string($message) && $message !== '' ? $message : null;
    }

    private function back(string $folder, string $query = ''): never
    {
        $folder = $folder === 'requests' ? 'requests' : 'friends';
        $target = '/app/friends?folder=' . $folder;
        $query = trim($query);
        if ($query !== '') {
            $target .= '&q=' . rawurlencode($query);
        }
        app()->redirect($target);
    }
}
