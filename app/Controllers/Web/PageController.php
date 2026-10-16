<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\View;

final class PageController
{
    /** @param array<string, string> $params */
    public function home(Request $request, array $params = []): void
    {
        $this->page('public.home', 'Tvá hra. Tvůj odkaz. | Dartiqo', 'reference-home');
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
        $this->page('public.dashboard', 'Objev svou hru | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function profile(Request $request, array $params = []): void
    {
        $this->page('public.profile', 'Objev svou hru | Dartiqo');
    }

    /** @param array<string, string> $params */
    public function settings(Request $request, array $params = []): void
    {
        $this->page('public.settings', 'Objev svou hru | Dartiqo');
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

    private function page(string $view, string $title, string $bodyClass = '', string $layout = 'layouts.public'): void
    {
        View::render($view, [
            'title' => $title,
            'bodyClass' => $bodyClass,
        ], $layout);
    }
}
