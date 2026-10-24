<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use PDOException;

/**
 * Úprava profilu z webu i z aplikace. Server je zdroj pravdy včetně fotky.
 */
final class ProfileService
{
    private const RESERVED = ['admin', 'administrator', 'dartiqo', 'support', 'root', 'system'];

    public function __construct(private readonly UserRepository $users)
    {
    }

    /**
     * @param array<string, mixed> $user aktuální řádek z DB
     * @param array{first?:string,last?:string,username?:string,email?:string,photo?:string|null} $input
     * @return array{ok:bool,user?:array<string,mixed>,errors?:array<string,string>,message?:string,status:int}
     */
    public function update(array $user, array $input): array
    {
        $id = (int) $user['id'];
        $first = array_key_exists('first', $input) ? trim((string) $input['first']) : (string) $user['first_name'];
        $last = array_key_exists('last', $input) ? trim((string) $input['last']) : (string) $user['last_name'];
        $username = array_key_exists('username', $input) ? trim((string) $input['username']) : (string) $user['username'];
        $email = array_key_exists('email', $input) ? mb_strtolower(trim((string) $input['email'])) : (string) $user['email'];

        $errors = [];
        if ($first === '' || mb_strlen($first) > 80) {
            $errors['first'] = 'Vyplň jméno (nejvýše 80 znaků).';
        }
        if ($last === '' || mb_strlen($last) > 80) {
            $errors['last'] = 'Vyplň příjmení (nejvýše 80 znaků).';
        }
        if (($error = $this->usernameError($username, $id)) !== null) {
            $errors['username'] = $error;
        }
        if (($error = $this->emailError($email, $id)) !== null) {
            $errors['email'] = $error;
        }

        $avatars = new AvatarStorage();
        $avatarPath = $user['avatar_path'] ?? null;
        $oldAvatar = is_string($avatarPath) ? $avatarPath : null;
        $newAvatar = $oldAvatar;
        $photoTouched = array_key_exists('photo', $input);

        if ($photoTouched) {
            $photo = $input['photo'];
            if ($photo === null || $photo === '') {
                $newAvatar = null;
            } elseif (is_string($photo) && str_starts_with($photo, 'data:image/')) {
                try {
                    $newAvatar = $avatars->storeDataUrl($photo);
                } catch (\InvalidArgumentException $e) {
                    $errors['photo'] = $e->getMessage();
                } catch (\RuntimeException $e) {
                    error_log('Dartiqo avatar: ' . $e->getMessage());

                    return ['ok' => false, 'message' => 'Profilovou fotku se teď nepodařilo uložit.', 'status' => 500];
                }
            } else {
                $errors['photo'] = 'Profilová fotka má neplatný formát.';
            }
        }

        if ($errors !== []) {
            if ($newAvatar !== $oldAvatar && $newAvatar !== null) {
                $avatars->delete($newAvatar);
            }

            return ['ok' => false, 'errors' => $errors, 'status' => 422];
        }

        try {
            $this->users->updateProfile($id, [
                'first_name' => $first,
                'last_name' => $last,
                'username' => $username,
                'email' => $email,
                'avatar_path' => $newAvatar,
            ]);
        } catch (PDOException $e) {
            if ($newAvatar !== $oldAvatar && $newAvatar !== null) {
                $avatars->delete($newAvatar);
            }
            if ($e->getCode() === '23000') {
                $field = str_contains($e->getMessage(), 'users_email_unique') ? 'email' : 'username';

                return [
                    'ok' => false,
                    'errors' => [$field => $field === 'email'
                        ? 'Tento e-mail už je zaregistrovaný.'
                        : 'Toto uživatelské jméno je obsazené.'],
                    'status' => 422,
                ];
            }
            throw $e;
        }

        if ($photoTouched && $oldAvatar !== null && $oldAvatar !== $newAvatar) {
            $avatars->delete($oldAvatar);
        }

        $fresh = $this->users->findById($id);
        if ($fresh === null) {
            return ['ok' => false, 'message' => 'Účet se nepodařilo načíst.', 'status' => 500];
        }

        return ['ok' => true, 'user' => UserRepository::toPublic($fresh), 'status' => 200];
    }

    private function usernameError(string $username, int $exceptId): ?string
    {
        if (!preg_match('/^[a-zA-Z0-9_]{3,24}$/', $username)) {
            return 'Použij 3–24 písmen, číslic nebo podtržítek.';
        }
        if (in_array(strtolower($username), self::RESERVED, true) || $this->users->usernameTaken($username, $exceptId)) {
            return 'Toto uživatelské jméno je obsazené.';
        }

        return null;
    }

    private function emailError(string $email, int $exceptId): ?string
    {
        if (strlen($email) > 191 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'Zadej platný e-mail.';
        }
        if ($this->users->emailTaken($email, $exceptId)) {
            return 'Tento e-mail už je zaregistrovaný.';
        }

        return null;
    }
}
