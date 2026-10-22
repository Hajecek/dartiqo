<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Repositories\UserRepository;
use App\Services\AvatarStorage;
use PDOException;

final class AuthController
{
    private const RESERVED_USERNAMES = ['admin', 'administrator', 'dartiqo', 'support', 'root', 'system'];

    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository(db());
    }

    /** @param array<string, string> $params */
    public function register(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);

        $data = [
            'first_name' => trim($request->input('first')),
            'last_name' => trim($request->input('last')),
            'username' => trim($request->input('username')),
            'email' => mb_strtolower(trim($request->input('email'))),
            'password' => $request->input('password'),
        ];
        $errors = $this->validateRegistration($data, $request->input('confirm'), (bool) ($request->all()['terms'] ?? false));
        if ($errors !== []) {
            $this->json(['ok' => false, 'errors' => $errors], 422);
        }

        $avatars = new AvatarStorage();
        $photo = $request->input('photo');
        try {
            $data['avatar_path'] = $photo !== '' ? $avatars->storeDataUrl($photo) : null;
        } catch (\InvalidArgumentException $e) {
            $this->json(['ok' => false, 'errors' => ['photo' => $e->getMessage()]], 422);
        } catch (\RuntimeException $e) {
            error_log('Dartiqo avatar: ' . $e->getMessage());
            $this->json(['ok' => false, 'errors' => ['photo' => 'Profilovou fotku se teď nepodařilo uložit. Zkus to znovu nebo krok přeskoč.']], 500);
        }

        try {
            $id = $this->users->create($data);
        } catch (PDOException $e) {
            $avatars->delete($data['avatar_path']);
            // 23000 = porušení unikátního klíče, když se dva lidé registrují se stejným jménem současně
            if ($e->getCode() === '23000') {
                $field = str_contains($e->getMessage(), 'users_email_unique') ? 'email' : 'username';
                $this->json(['ok' => false, 'errors' => [$field => $this->takenMessage($field)]], 422);
            }
            throw $e;
        }

        $user = (array) $this->users->findById($id);
        auth()->login($user);
        $this->json(['ok' => true, 'user' => UserRepository::toPublic($user), 'csrf' => auth()->csrfToken()], 201);
    }

    /** @param array<string, string> $params */
    public function login(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);

        $identity = trim($request->input('identity'));
        $password = $request->input('password');
        if ($identity === '' || $password === '') {
            $this->json(['ok' => false, 'message' => 'Vyplň přihlašovací údaje.'], 422);
        }
        if (str_contains($identity, '@')) {
            $identity = mb_strtolower($identity);
        }

        $user = auth()->attempt($identity, $password, (bool) ($request->all()['remember'] ?? false));
        if ($user === null) {
            $this->json(['ok' => false, 'message' => 'Nesprávný e-mail, uživatelské jméno nebo heslo.'], 401);
        }

        $this->json(['ok' => true, 'user' => UserRepository::toPublic($user), 'csrf' => auth()->csrfToken()]);
    }

    /** @param array<string, string> $params */
    public function logout(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        auth()->logout();
        $this->json(['ok' => true]);
    }

    /** @param array<string, string> $params */
    public function availability(Request $request, array $params = []): void
    {
        $field = $request->input('field');
        $value = trim($request->input('value'));
        if ($field === 'username') {
            $error = $this->usernameError($value);
        } elseif ($field === 'email') {
            $error = $this->emailError(mb_strtolower($value));
        } else {
            $this->json(['ok' => false, 'message' => 'Neznámé pole.'], 400);
        }

        $this->json(['ok' => $error === null, 'message' => $error]);
    }

    /**
     * @param array<string, string> $data
     * @return array<string, string>
     */
    private function validateRegistration(array $data, string $confirm, bool $terms): array
    {
        $errors = [];
        if ($data['first_name'] === '' || mb_strlen($data['first_name']) > 80) {
            $errors['first'] = 'Vyplň jméno (nejvýše 80 znaků).';
        }
        if ($data['last_name'] === '' || mb_strlen($data['last_name']) > 80) {
            $errors['last'] = 'Vyplň příjmení (nejvýše 80 znaků).';
        }
        if (($error = $this->usernameError($data['username'])) !== null) {
            $errors['username'] = $error;
        }
        if (($error = $this->emailError($data['email'])) !== null) {
            $errors['email'] = $error;
        }
        $length = mb_strlen($data['password']);
        if ($length < 12 || $length > 128) {
            $errors['password'] = 'Heslo musí mít 12 až 128 znaků.';
        } elseif (!hash_equals($data['password'], $confirm)) {
            $errors['password'] = 'Hesla se neshodují.';
        }
        if (!$terms) {
            $errors['terms'] = 'Pro pokračování potvrď podmínky a zásady soukromí.';
        }

        return $errors;
    }

    private function usernameError(string $username): ?string
    {
        if (!preg_match('/^[a-zA-Z0-9_]{3,24}$/', $username)) {
            return 'Použij 3–24 písmen, číslic nebo podtržítek.';
        }
        if (in_array(strtolower($username), self::RESERVED_USERNAMES, true) || $this->users->usernameExists($username)) {
            return $this->takenMessage('username');
        }

        return null;
    }

    private function emailError(string $email): ?string
    {
        if (strlen($email) > 191 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'Zadej platný e-mail.';
        }
        if ($this->users->emailExists($email)) {
            return $this->takenMessage('email');
        }

        return null;
    }

    private function takenMessage(string $field): string
    {
        return $field === 'email'
            ? 'Tento e-mail už je zaregistrovaný. Zkus se přihlásit.'
            : 'Toto uživatelské jméno je obsazené.';
    }

    private function requireCsrf(Request $request): void
    {
        if (!auth()->verifyCsrf($request->header('X-CSRF-Token'))) {
            $this->json(['ok' => false, 'message' => 'Platnost formuláře vypršela. Obnov stránku a zkus to znovu.'], 419);
        }
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
