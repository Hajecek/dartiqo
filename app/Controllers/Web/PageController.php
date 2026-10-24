<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\View;
use App\Repositories\FriendRepository;
use App\Repositories\MatchRepository;
use App\Services\MatchInsights;

final class PageController
{
    /** @param array<string, string> $params */
    public function home(Request $request, array $params = []): void
    {
        $this->page('public.home', 'Tvá hra. Tvůj odkaz. | Dartiqo', 'is-home');
    }

    /** @param array<string, string> $params */
    public function gameModes(Request $request, array $params = []): void
    {
        $this->page('public.game-modes', 'Herní módy | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function mode(Request $request, array $params = []): void
    {
        $mode = (string) ($params['mode'] ?? '');
        $allowed = ['x01', 'cricket', 'dartbot', 'local', 'online', 'ranked', 'training', 'career'];
        if (!in_array($mode, $allowed, true)) {
            app()->abort(404);
        }
        $this->page('public.modes.' . $mode, 'Objev svou hru | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function training(Request $request, array $params = []): void
    {
        $this->page('public.training', 'Trénink | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function career(Request $request, array $params = []): void
    {
        $this->page('public.career', 'Kariéra | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function online(Request $request, array $params = []): void
    {
        $this->page('public.online', 'Online | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function statistics(Request $request, array $params = []): void
    {
        $this->page('public.statistics', 'Statistiky | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function about(Request $request, array $params = []): void
    {
        $this->page('public.about', 'O Dartiqo | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function features(Request $request, array $params = []): void
    {
        $this->page('public.features', 'Objev svou hru | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function contact(Request $request, array $params = []): void
    {
        $this->page('public.contact', 'Objev svou hru | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function leaderboards(Request $request, array $params = []): void
    {
        $this->page('public.leaderboards', 'Objev svou hru | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function tournaments(Request $request, array $params = []): void
    {
        $this->page('public.tournaments', 'Objev svou hru | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function dashboard(Request $request, array $params = []): void
    {
        $this->page('public.dashboard', 'Přehled | Dartiqo', 'app-body', 'layouts.app', [
            'insights' => $this->insights(),
            'incomingFriends' => $this->incomingFriendCount(),
        ]);
    }

    /** @param array<string, string> $params */
    public function appStatistics(Request $request, array $params = []): void
    {
        $this->page('public.app-statistics', 'Statistiky | Dartiqo', 'app-body', 'layouts.app', ['insights' => $this->insights()]);
    }

    /** @param array<string, string> $params */
    public function appGames(Request $request, array $params = []): void
    {
        $id = strtolower((string) ($params['id'] ?? ''));
        $this->page(
            'public.app-games',
            $id !== '' ? 'Detail zápasu | Dartiqo' : 'Moje hry | Dartiqo',
            'app-body',
            'layouts.app',
            ['matchId' => $id, 'insights' => $this->insights()]
        );
    }

    /** @param array<string, string> $params */
    public function appCareer(Request $request, array $params = []): void
    {
        $this->page('public.app-career', 'Kariéra | Dartiqo', 'app-body', 'layouts.app');
    }

    /** @param array<string, string> $params */
    public function live(Request $request, array $params = []): void
    {
        $this->page('public.live', 'Živě | Dartiqo', 'app-body', 'layouts.live');
    }

    /** @param array<string, string> $params */
    public function profile(Request $request, array $params = []): void
    {
        $this->page('public.profile', 'Profil | Dartiqo', 'app-body', 'layouts.app', [
            'insights' => $this->insights(),
            'incomingFriends' => $this->incomingFriendCount(),
        ]);
    }

    /** @param array<string, string> $params */
    public function settings(Request $request, array $params = []): void
    {
        $this->page('public.settings', 'Nastavení | Dartiqo', 'app-body', 'layouts.app');
    }

    /** @param array<string, string> $params */
    public function login(Request $request, array $params = []): void
    {
        $this->page('auth.login', 'Objev svou hru | Dartiqo', '', 'layouts.auth');
    }

    /** @param array<string, string> $params */
    public function register(Request $request, array $params = []): void
    {
        $this->page('auth.register', 'Objev svou hru | Dartiqo', '', 'layouts.auth');
    }

    /** @param array<string, string> $params */
    public function forgotPassword(Request $request, array $params = []): void
    {
        $this->page('auth.forgot-password', 'Objev svou hru | Dartiqo', '', 'layouts.auth');
    }

    /** @param array<string, string> $params */
    public function resetPassword(Request $request, array $params = []): void
    {
        $this->page('auth.reset-password', 'Objev svou hru | Dartiqo', '', 'layouts.auth');
    }

    private function incomingFriendCount(): int
    {
        $user = auth()->user();
        if ($user === null) {
            return 0;
        }

        return count((new FriendRepository(db()))->book((int) $user['id'])['incoming']);
    }

    /** Zápasy přihlášeného hráče. Jiný účet k nim přístup nemá. */
    private function insights(): MatchInsights
    {
        $user = auth()->user();

        return new MatchInsights($user === null ? [] : (new MatchRepository(db()))->allForUser((int) $user['id']));
    }

    /** @param array<string, mixed> $data */
    private function page(string $view, string $title, string $bodyClass = '', string $layout = 'layouts.public', array $data = []): void
    {
        if (in_array($layout, ['layouts.app', 'layouts.live'], true) && !auth()->check()) {
            app()->redirect('/login');
        }
        if ($layout === 'layouts.auth' && auth()->check()) {
            app()->redirect('/app');
        }

        View::render($view, array_merge([
            'title' => $title,
            'bodyClass' => $bodyClass,
        ], $data), $layout);
    }
}
