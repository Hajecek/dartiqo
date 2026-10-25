<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\MatchStats;
use App\Services\OnlineMatchException;
use App\Services\OnlineX01;
use App\Services\PushService;
use InvalidArgumentException;
use PDOException;
use RuntimeException;

/**
 * Společný online zápas. Telefon sem posílá jednu šipku, skóre a tah drží server.
 * Do matches se stav jen zrcadlí, ať živý web a statistiky zůstanou na původním řádku.
 */
final class OnlineMatchRepository
{
    private const LOBBY_SECONDS = 900;
    private const LIVE_STALE_SECONDS = 2700;
    private const JOIN_MAX_FAILURES = 5;
    private const JOIN_LOCK_SECONDS = 600;
    /** Stejný tvar jako u přihlášení, ať chybějící kód netrvá kratší dobu než špatné heslo. */
    private const DUMMY_HASH = '$2y$10$cfTFYdLxJUiEHUjxUx5X5OQflWLcI1W39ADvTg6Be.EqDGD.Xl5z2';

    public function __construct(private readonly Database $db)
    {
    }

    /**
     * @param array<string, mixed> $user
     * @param array{rules: array<string, mixed>, kind:string, visibility:string, password:?string} $lobby
     * @return array<string, mixed>
     */
    public function create(array $user, array $lobby): array
    {
        $rules = $lobby['rules'];
        $hash = self::hash((string) ($lobby['password'] ?? ''));

        return $this->db->transaction(function (Database $db) use ($user, $rules, $hash, $lobby): array {
            $userId = (int) $user['id'];
            $this->lockUser($db, $userId);
            $this->releaseRows($db, $userId, $this->lockInOrder($db, $this->openIds($db, $userId)));
            $now = time();
            $state = [
                'leg' => 1,
                'winner' => null,
                'legWinner' => null,
                'openingStarter' => 0,
                'startedAt' => null,
                'lastDartId' => null,
                'visitStart' => $rules['startingScore'],
                'visit' => [],
                'visits' => [],
                'title' => self::name($user),
                'players' => [$this->player($user, $rules['startingScore'])],
            ];
            $matchId = 0;
            for ($attempt = 0; ; $attempt++) {
                try {
                    $matchId = $db->insert('online_matches', [
                        'public_id' => self::publicId(),
                        'host_user_id' => $userId,
                        'kind' => $lobby['kind'],
                        'visibility' => $lobby['visibility'],
                        'code' => self::lobbyCode(),
                        'password_hash' => $hash,
                        'status' => 'lobby',
                        'rules' => self::json($rules),
                        'active_slot' => 0,
                        'seq' => 0,
                        'state' => self::json($state),
                        'expires_at' => gmdate('Y-m-d H:i:s', $now + self::LOBBY_SECONDS),
                    ]);
                    break;
                } catch (PDOException $e) {
                    if ($e->getCode() !== '23000' || $attempt >= 4) {
                        throw $e;
                    }
                }
            }
            $db->insert('online_match_players', [
                'online_match_id' => $matchId,
                'user_id' => $userId,
                'slot' => 0,
                'ready' => 0,
            ]);

            return $this->present($db, $matchId, $userId);
        });
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function join(array $user, string $code, string $password, int $inviteId = 0): array
    {
        $error = null;
        $notice = null;
        $view = $this->db->transaction(function (Database $db) use ($user, $code, $password, $inviteId, &$error, &$notice): array {
            $userId = (int) $user['id'];
            $this->lockUser($db, $userId);
            $this->assertNotLocked($db, $userId, $code);
            $peek = $db->fetch('SELECT id FROM online_matches WHERE code = :code', ['code' => $code]);
            $ids = $this->openIds($db, $userId);
            if ($peek !== null) {
                $ids[] = (int) $peek['id'];
            }
            $locked = $this->lockInOrder($db, $ids);
            $match = $peek !== null ? ($locked[(int) $peek['id']] ?? null) : null;
            if ($match === null || (string) ($match['code'] ?? '') !== $code) {
                $this->burnPasswordTime($password);
                $this->failJoin($db, $userId, $code);
                $error = new OnlineMatchException('Kód nebo heslo nesedí.', 422);

                return [];
            }
            if ($this->member($this->players($db, (int) $match['id']), $userId) !== null) {
                $this->clearAttempts($db, $userId, $code);
                if ($this->trustedInvite($db, $inviteId, $userId, (int) $match['id'])) {
                    $this->acceptInviteRow($db, $inviteId);
                }
                $match = $this->settle($db, $match);

                return $this->present($db, (int) $match['id'], $userId);
            }
            $match = $this->settle($db, $match);
            if ($match['status'] !== 'lobby') {
                $error = new OnlineMatchException(
                    $match['status'] === 'abandoned' ? 'Lobby vypršelo.' : 'Do tohohle zápasu se nedá vstoupit.',
                    409,
                    null,
                    $this->present($db, (int) $match['id'], $userId)
                );

                return [];
            }
            $trusted = $this->trustedInvite($db, $inviteId, $userId, (int) $match['id']);
            if (!$trusted && !$this->passwordOk($match, $password)) {
                $this->failJoin($db, $userId, $code);
                $error = new OnlineMatchException('Kód nebo heslo nesedí.', 422);

                return [];
            }
            $this->clearAttempts($db, $userId, $code);
            $error = $this->seat($db, $user, $match, $locked, $trusted ? $inviteId : 0);
            if ($error !== null) {
                return [];
            }
            $notice = $this->joinedNotice($user, $match);

            return $this->present($db, (int) $match['id'], $userId);
        });
        if ($error !== null) {
            throw $error;
        }
        $this->notify($notice);

        return $view;
    }

    /**
     * Vstup do veřejného lobby ze seznamu. Kód ani heslo nejsou potřeba.
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function joinPublic(array $user, string $publicId): array
    {
        $error = null;
        $notice = null;
        $view = $this->db->transaction(function (Database $db) use ($user, $publicId, &$error, &$notice): array {
            $userId = (int) $user['id'];
            $this->lockUser($db, $userId);
            $peek = $db->fetch('SELECT id FROM online_matches WHERE public_id = :id', ['id' => $publicId]);
            if ($peek === null) {
                $error = new OnlineMatchException('Lobby už neexistuje.', 404);

                return [];
            }
            $ids = $this->openIds($db, $userId);
            $ids[] = (int) $peek['id'];
            $locked = $this->lockInOrder($db, $ids);
            $match = $locked[(int) $peek['id']] ?? null;
            if ($match === null) {
                $error = new OnlineMatchException('Lobby už neexistuje.', 404);

                return [];
            }
            if ($this->member($this->players($db, (int) $match['id']), $userId) !== null) {
                $match = $this->settle($db, $match);

                return $this->present($db, (int) $match['id'], $userId);
            }
            $match = $this->settle($db, $match);
            if ($match['status'] !== 'lobby') {
                $error = new OnlineMatchException('Lobby už není otevřené.', 409);

                return [];
            }
            if ($match['visibility'] !== 'public') {
                $error = new OnlineMatchException('Do uzavřeného lobby se vstupuje kódem.', 403);

                return [];
            }
            $error = $this->seat($db, $user, $match, $locked, 0);
            if ($error !== null) {
                return [];
            }
            $notice = $this->joinedNotice($user, $match);

            return $this->present($db, (int) $match['id'], $userId);
        });
        if ($error !== null) {
            throw $error;
        }
        $this->notify($notice);

        return $view;
    }

    /**
     * Hostitel změní pravidla, typ nebo viditelnost lobby. Připravenost obou se tím ruší.
     *
     * @param array<string, mixed> $user
     * @param array{rules: array<string, mixed>, kind:string, visibility:string, password:?string} $lobby
     * @return array<string, mixed>
     */
    public function update(array $user, string $publicId, array $lobby): array
    {
        $hash = $lobby['password'] === null ? null : self::hash($lobby['password']);
        $error = null;
        $view = $this->db->transaction(function (Database $db) use ($user, $publicId, $lobby, $hash, &$error): array {
            $userId = (int) $user['id'];
            $this->lockUser($db, $userId);
            $match = $this->settle($db, $this->requireMatch($db, $publicId));
            $this->requireMember($this->players($db, (int) $match['id']), $userId);
            if ((int) $match['host_user_id'] !== $userId) {
                $error = new OnlineMatchException('Lobby upravuje hostitel.', 403, null, $this->present($db, (int) $match['id'], $userId));

                return [];
            }
            if ($match['status'] !== 'lobby') {
                $error = new OnlineMatchException('Upravit jde jen lobby, které ještě nezačalo.', 409, null, $this->present($db, (int) $match['id'], $userId));

                return [];
            }
            $rules = $lobby['rules'];
            $state = $this->state($match);
            foreach ($state['players'] as &$player) {
                $player['remaining'] = $rules['startingScore'];
            }
            unset($player);
            $state['visitStart'] = $rules['startingScore'];
            $db->update('online_matches', [
                'kind' => $lobby['kind'],
                'visibility' => $lobby['visibility'],
                'password_hash' => $lobby['password'] === null ? $match['password_hash'] : $hash,
                'rules' => self::json($rules),
                'state' => self::json($state),
                'seq' => (int) $match['seq'] + 1,
                'expires_at' => gmdate('Y-m-d H:i:s', time() + self::LOBBY_SECONDS),
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => (int) $match['id']]);
            $db->query('UPDATE online_match_players SET ready = 0 WHERE online_match_id = :id', ['id' => (int) $match['id']]);

            return $this->present($db, (int) $match['id'], $userId);
        });
        if ($error !== null) {
            throw $error;
        }

        return $view;
    }

    /**
     * Veřejná lobby, do kterých jde vstoupit ze seznamu. Vlastní lobby v seznamu není.
     *
     * @return list<array<string, mixed>>
     */
    public function lobbies(int $viewerId, ?string $kind): array
    {
        $params = ['viewer' => $viewerId, 'now' => gmdate('Y-m-d H:i:s')];
        $filter = '';
        if ($kind !== null) {
            $filter = ' AND m.kind = :kind';
            $params['kind'] = $kind;
        }
        $rows = $this->db->fetchAll(
            'SELECT m.public_id, m.kind, m.rules, m.created_at,
                    u.public_id AS host_public_id, u.username, u.first_name, u.last_name,
                    u.avatar_path, u.profile_revision,
                    COALESCE(r.rating, 1000) AS rating,
                    (SELECT COUNT(*) FROM online_match_players p WHERE p.online_match_id = m.id) AS seated
             FROM online_matches m
             JOIN users u ON u.id = m.host_user_id
             LEFT JOIN online_ratings r ON r.user_id = m.host_user_id
             WHERE m.status = \'lobby\' AND m.visibility = \'public\' AND m.expires_at > :now' . $filter . '
               AND NOT EXISTS (
                   SELECT 1 FROM online_match_players mine
                   WHERE mine.online_match_id = m.id AND mine.user_id = :viewer
               )
             ORDER BY m.created_at DESC
             LIMIT 50',
            $params
        );
        $list = [];
        foreach ($rows as $row) {
            $seated = (int) $row['seated'];
            $list[] = [
                'id' => (string) $row['public_id'],
                'kind' => (string) $row['kind'],
                'rules' => $this->rules($row),
                'host' => [
                    'name' => self::name($row),
                    'username' => (string) $row['username'],
                    'publicId' => (string) $row['host_public_id'],
                    'photo' => self::photo($row),
                    'rating' => (int) $row['rating'],
                ],
                'seated' => $seated,
                'open' => $seated < 2,
                'createdAt' => gmdate('c', (int) strtotime((string) $row['created_at'] . ' UTC')),
            ];
        }

        return $list;
    }

    /** @return array{rating:int,played:int,wins:int} */
    public function rating(int $userId): array
    {
        $row = $this->db->fetch('SELECT rating, played, wins FROM online_ratings WHERE user_id = :user', ['user' => $userId]);

        return [
            'rating' => (int) ($row['rating'] ?? 1000),
            'played' => (int) ($row['played'] ?? 0),
            'wins' => (int) ($row['wins'] ?? 0),
        ];
    }

    /**
     * Posadí hráče na druhé místo. Chybu vrací, ať transakce commitne uvolnění a pokusy.
     *
     * @param array<string, mixed> $user
     * @param array<string, mixed> $match
     * @param array<int, array<string, mixed>> $locked
     */
    private function seat(Database $db, array $user, array $match, array $locked, int $inviteId): ?OnlineMatchException
    {
        $userId = (int) $user['id'];
        if (count($this->players($db, (int) $match['id'])) >= 2) {
            return new OnlineMatchException('Lobby je plné.', 409, 'full');
        }
        try {
            $others = $locked;
            unset($others[(int) $match['id']]);
            $this->releaseRows($db, $userId, $others);
        } catch (OnlineMatchException $busy) {
            return $busy;
        }
        $rules = $this->rules($match);
        $state = $this->state($match);
        $state['players'][] = $this->player($user, $rules['startingScore']);
        $db->insert('online_match_players', [
            'online_match_id' => (int) $match['id'],
            'user_id' => $userId,
            'slot' => 1,
            'ready' => 0,
        ]);
        if ($inviteId > 0) {
            $this->acceptInviteRow($db, $inviteId);
        }
        $db->update('online_matches', [
            'state' => self::json($state),
            'seq' => (int) $match['seq'] + 1,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + self::LOBBY_SECONDS),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => (int) $match['id']]);

        return null;
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $match
     * @return array{userId:int,title:string,body:string,type:string}
     */
    private function joinedNotice(array $user, array $match): array
    {
        return [
            'userId' => (int) $match['host_user_id'],
            'title' => 'Online lobby',
            'body' => self::name($user) . ' je v lobby.',
            'type' => 'online.joined',
        ];
    }

    /**
     * Hostitel, který má lobby otevřené, ho drží naživu. Zapisuje se nejvýš jednou za minutu.
     *
     * @param array<string, mixed> $match
     */
    private function keepAlive(Database $db, array $match, int $userId): void
    {
        if ($match['status'] !== 'lobby' || (int) $match['host_user_id'] !== $userId || $match['expires_at'] === null) {
            return;
        }
        $expires = strtotime((string) $match['expires_at'] . ' UTC');
        if ($expires !== false && $expires > time() + self::LOBBY_SECONDS - 60) {
            return;
        }
        $db->update('online_matches', [
            'expires_at' => gmdate('Y-m-d H:i:s', time() + self::LOBBY_SECONDS),
        ], 'id = :id', ['id' => (int) $match['id']]);
    }

    /**
     * Elo pro ranked. Vítěz bere body poraženému, změna se uloží do stavu zápasu.
     *
     * @param array<string, mixed> $match
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function rate(Database $db, array $match, array $state, int $winnerSlot): array
    {
        if (($match['kind'] ?? 'classic') !== 'ranked' || isset($state['rating'])) {
            return $state;
        }
        $ids = [];
        foreach ([0, 1] as $slot) {
            $player = $state['players'][$slot] ?? null;
            if (!is_array($player)) {
                return $state;
            }
            $ids[$slot] = (int) $player['userId'];
        }
        $sorted = array_values($ids);
        sort($sorted);
        foreach ($sorted as $id) {
            $db->query('INSERT IGNORE INTO online_ratings (user_id) VALUES (:user)', ['user' => $id]);
        }
        $ratings = [];
        foreach ($sorted as $id) {
            $row = $db->fetch('SELECT rating FROM online_ratings WHERE user_id = :user FOR UPDATE', ['user' => $id]);
            $ratings[$id] = (int) ($row['rating'] ?? 1000);
        }
        $change = [];
        foreach ($ids as $slot => $id) {
            $mine = $ratings[$id];
            $theirs = $ratings[$ids[1 - $slot]];
            $expected = 1 / (1 + 10 ** (($theirs - $mine) / 400));
            $won = $slot === $winnerSlot;
            $delta = (int) round(32 * (($won ? 1 : 0) - $expected));
            $after = max(100, min(4000, $mine + $delta));
            $change[$slot] = ['before' => $mine, 'after' => $after, 'delta' => $after - $mine];
            $db->query(
                'UPDATE online_ratings SET rating = :rating, played = played + 1, wins = wins + :won WHERE user_id = :user',
                ['rating' => $after, 'won' => $won ? 1 : 0, 'user' => $id]
            );
        }
        $state['rating'] = $change;

        return $state;
    }

    /** @return array<string, mixed>|null */
    public function current(int $userId): ?array
    {
        return $this->db->transaction(function (Database $db) use ($userId): ?array {
            $this->lockUser($db, $userId);
            $row = $db->fetch(
                'SELECT m.id
                 FROM online_match_players p
                 JOIN online_matches m ON m.id = p.online_match_id
                 WHERE p.user_id = :user AND m.status IN (\'lobby\', \'live\')
                 ORDER BY m.id DESC
                 LIMIT 1',
                ['user' => $userId]
            );
            if ($row === null) {
                return null;
            }
            $match = $this->settle($db, $this->lockId($db, (int) $row['id']));
            if (!in_array($match['status'], ['lobby', 'live'], true)) {
                return null;
            }
            $this->keepAlive($db, $match, $userId);

            return $this->present($db, (int) $match['id'], $userId);
        });
    }

    /** @return array<string, mixed> */
    public function show(int $userId, string $publicId): array
    {
        return $this->db->transaction(function (Database $db) use ($userId, $publicId): array {
            $this->lockUser($db, $userId);
            $match = $this->requireMatch($db, $publicId);
            $this->requireMember($this->players($db, (int) $match['id']), $userId);
            $match = $this->settle($db, $match);
            $this->keepAlive($db, $match, $userId);

            return $this->present($db, (int) $match['id'], $userId);
        });
    }

    /** @return array<string, mixed> */
    public function ready(int $userId, string $publicId, bool $ready): array
    {
        return $this->db->transaction(function (Database $db) use ($userId, $publicId, $ready): array {
            $this->lockUser($db, $userId);
            $match = $this->settle($db, $this->requireMatch($db, $publicId));
            $member = $this->requireMember($this->players($db, (int) $match['id']), $userId);
            if ($match['status'] !== 'lobby') {
                return $this->present($db, (int) $match['id'], $userId);
            }
            $flag = $ready ? 1 : 0;
            if ((int) $member['ready'] === $flag) {
                return $this->present($db, (int) $match['id'], $userId);
            }
            $db->update('online_match_players', ['ready' => $flag], 'online_match_id = :match AND user_id = :user', [
                'match' => (int) $match['id'],
                'user' => $userId,
            ]);
            $db->update('online_matches', [
                'seq' => (int) $match['seq'] + 1,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => (int) $match['id']]);

            return $this->present($db, (int) $match['id'], $userId);
        });
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function start(array $user, string $publicId): array
    {
        $error = null;
        $notice = null;
        $view = $this->db->transaction(function (Database $db) use ($user, $publicId, &$error, &$notice): array {
            $userId = (int) $user['id'];
            $this->lockUser($db, $userId);
            $match = $this->settle($db, $this->requireMatch($db, $publicId));
            $this->requireMember($this->players($db, (int) $match['id']), $userId);
            if ((int) $match['host_user_id'] !== $userId) {
                $error = new OnlineMatchException('Zápas spouští hostitel.', 403, null, $this->present($db, (int) $match['id'], $userId));

                return [];
            }
            if ($match['status'] === 'live') {
                return $this->present($db, (int) $match['id'], $userId);
            }
            if ($match['status'] !== 'lobby') {
                $error = new OnlineMatchException('Lobby vypršelo.', 409, null, $this->present($db, (int) $match['id'], $userId));

                return [];
            }
            $players = $this->players($db, (int) $match['id']);
            if (count($players) < 2 || count($this->state($match)['players']) < 2) {
                $error = new OnlineMatchException('Čeká se na soupeře.', 409, null, $this->present($db, (int) $match['id'], $userId));

                return [];
            }
            foreach ($players as $player) {
                if ((int) $player['ready'] !== 1) {
                    $error = new OnlineMatchException('Oba hráči musí být připravení.', 409, null, $this->present($db, (int) $match['id'], $userId));

                    return [];
                }
            }
            $now = time();
            $rules = $this->rules($match);
            $state = OnlineX01::kickoff($this->state($match), $rules, $now);
            $match['seq'] = (int) $match['seq'] + 1;
            $match['status'] = 'live';
            $match['active_slot'] = 0;
            $this->persist($db, $match, $state, 'live', (int) $match['seq'], 0, false, null, gmdate('Y-m-d H:i:s', $now));
            $this->mirror($db, $match, $rules, $state, 'live');
            foreach ($players as $player) {
                if ((int) $player['user_id'] !== $userId) {
                    $notice = [
                        'userId' => (int) $player['user_id'],
                        'title' => 'Online zápas',
                        'body' => 'Zápas začal.',
                        'type' => 'online.started',
                    ];
                }
            }

            return $this->present($db, (int) $match['id'], $userId);
        });
        if ($error !== null) {
            throw $error;
        }
        $this->notify($notice);

        return $view;
    }

    /**
     * @param array<string, mixed> $user
     * @param array{seq:int,dartId:string,segment:int,multiplier:int,label:string,score:int} $dart
     * @return array<string, mixed>
     */
    public function throwDart(array $user, string $publicId, array $dart): array
    {
        $error = null;
        $notice = null;
        $view = $this->db->transaction(function (Database $db) use ($user, $publicId, $dart, &$error, &$notice): array {
            $userId = (int) $user['id'];
            $this->lockUser($db, $userId);
            $match = $this->settle($db, $this->requireMatch($db, $publicId));
            $member = $this->requireMember($this->players($db, (int) $match['id']), $userId);
            $view = $this->present($db, (int) $match['id'], $userId);
            if ($match['status'] !== 'live') {
                $error = new OnlineMatchException('Teď se házet nedá.', 409, 'match_not_live', $view);

                return [];
            }
            $state = $this->state($match);
            if (($state['lastDartId'] ?? null) === $dart['dartId']) {
                return $view;
            }
            if ((int) $match['seq'] !== $dart['seq']) {
                $error = new OnlineMatchException('Stav zápasu se změnil. Načti ho znovu.', 409, 'stale_seq', $view);

                return [];
            }
            $slot = (int) $member['slot'];
            if ((int) $match['active_slot'] !== $slot) {
                $error = new OnlineMatchException('Není tvoje řada.', 409, 'not_your_turn', $view);

                return [];
            }
            if (count($state['visit'] ?? []) >= 3) {
                $error = new OnlineMatchException('Návštěva už má tři šipky.', 409, 'visit_full', $view);

                return [];
            }
            try {
                $result = OnlineX01::apply($state, $this->rules($match), $slot, $dart);
            } catch (InvalidArgumentException $e) {
                $error = new OnlineMatchException($e->getMessage(), 409, 'visit_full', $view);

                return [];
            }
            $state = $result['state'];
            if ($result['finished']) {
                $state = $this->rate($db, $match, $state, (int) $state['winner']);
            }
            $status = $result['finished'] ? 'finished' : 'live';
            $match['seq'] = (int) $match['seq'] + 1;
            $match['status'] = $status;
            $match['active_slot'] = $result['activeSlot'];
            $finishedAt = $result['finished'] ? gmdate('Y-m-d H:i:s') : null;
            $this->persist($db, $match, $state, $status, (int) $match['seq'], $result['activeSlot'], $result['finished'], $finishedAt, null);
            $this->mirror($db, $match, $this->rules($match), $state, $status);
            if (!$result['finished'] && $result['activeSlot'] !== $slot) {
                $next = $state['players'][$result['activeSlot']] ?? null;
                if (is_array($next)) {
                    $notice = [
                        'userId' => (int) $next['userId'],
                        'title' => 'Jsi na tahu',
                        'body' => self::name($user) . ' dohrál návštěvu.',
                        'type' => 'online.turn',
                    ];
                }
            }

            return $this->present($db, (int) $match['id'], $userId);
        });
        if ($error !== null) {
            throw $error;
        }
        $this->notify($notice);

        return $view;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function abandon(array $user, string $publicId): array
    {
        $notice = null;
        $view = $this->db->transaction(function (Database $db) use ($user, $publicId, &$notice): array {
            $userId = (int) $user['id'];
            $this->lockUser($db, $userId);
            $match = $this->requireMatch($db, $publicId);
            $member = $this->requireMember($this->players($db, (int) $match['id']), $userId);
            if ($match['status'] === 'finished') {
                throw new OnlineMatchException('Zápas už skončil.', 409, null, $this->present($db, (int) $match['id'], $userId));
            }
            if ($match['status'] === 'abandoned') {
                return $this->present($db, (int) $match['id'], $userId);
            }
            if ($match['status'] === 'lobby' && (int) $member['slot'] !== 0) {
                $this->removePlayer($db, $match, $userId);
                $notice = [
                    'userId' => (int) $match['host_user_id'],
                    'title' => 'Online lobby',
                    'body' => self::name($user) . ' odešel z lobby.',
                    'type' => 'online.left',
                ];

                return $this->present($db, (int) $match['id'], $userId);
            }
            $forfeit = $match['status'] === 'live' && ($match['kind'] ?? 'classic') === 'ranked' && !$this->stale($match);
            if ($forfeit) {
                $state = $this->rate($db, $match, $this->state($match), 1 - (int) $member['slot']);
                $db->update('online_matches', ['state' => self::json($state)], 'id = :id', ['id' => (int) $match['id']]);
            }
            $this->markAbandoned($db, (int) $match['id'], (int) $match['seq']);
            if ($match['status'] === 'live') {
                $this->deleteMirrors($db, (int) $match['id']);
            }
            foreach ($this->players($db, (int) $match['id']) as $player) {
                if ((int) $player['user_id'] !== $userId) {
                    $notice = [
                        'userId' => (int) $player['user_id'],
                        'title' => 'Online zápas',
                        'body' => match (true) {
                            $forfeit => 'Soupeř zápas opustil. Výhra se ti započítala do ratingu.',
                            $match['status'] === 'live' => 'Soupeř zápas opustil.',
                            default => 'Hostitel lobby zrušil.',
                        },
                        'type' => 'online.abandoned',
                    ];
                }
            }

            return $this->present($db, (int) $match['id'], $userId);
        });
        $this->notify($notice);

        return $view;
    }

    /**
     * Pozve kamaráda do lobby. Přijetí ho posadí do zápasu, heslo nepotřebuje.
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function invite(array $user, string $publicId, int $friendId): array
    {
        $userId = (int) $user['id'];
        if ($friendId === $userId) {
            throw new OnlineMatchException('Sebe pozvat nejde.', 422);
        }
        $this->assertFriends($userId, $friendId);
        $notice = null;
        $view = $this->db->transaction(function (Database $db) use ($user, $userId, $publicId, $friendId, &$notice): array {
            $this->lockUser($db, $userId);
            $match = $this->settle($db, $this->requireMatch($db, $publicId));
            $this->requireMember($this->players($db, (int) $match['id']), $userId);
            if ($match['status'] !== 'lobby') {
                throw new OnlineMatchException('Pozvat jde jen do lobby.', 409, null, $this->present($db, (int) $match['id'], $userId));
            }
            if ($this->member($this->players($db, (int) $match['id']), $friendId) !== null) {
                throw new OnlineMatchException('Kamarád už v lobby je.', 409, null, $this->present($db, (int) $match['id'], $userId));
            }
            $now = gmdate('Y-m-d H:i:s');
            $existing = $db->fetch(
                'SELECT id, status FROM online_invites WHERE online_match_id = :match AND invitee_id = :user FOR UPDATE',
                ['match' => (int) $match['id'], 'user' => $friendId]
            );
            $fresh = false;
            if ($existing === null) {
                try {
                    $db->insert('online_invites', [
                        'online_match_id' => (int) $match['id'],
                        'inviter_id' => $userId,
                        'invitee_id' => $friendId,
                        'status' => 'pending',
                        'updated_at' => $now,
                    ]);
                    $fresh = true;
                } catch (PDOException $e) {
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }
                }
            } elseif ($existing['status'] !== 'pending') {
                $db->update('online_invites', [
                    'inviter_id' => $userId,
                    'status' => 'pending',
                    'updated_at' => $now,
                ], 'id = :id', ['id' => (int) $existing['id']]);
                $fresh = true;
            }
            if ($fresh) {
                $db->update('online_matches', [
                    'seq' => (int) $match['seq'] + 1,
                    'updated_at' => $now,
                ], 'id = :id', ['id' => (int) $match['id']]);
                $notice = [
                    'userId' => $friendId,
                    'title' => 'Pozvánka do zápasu',
                    'body' => self::name($user) . ' tě zve do online lobby.',
                    'type' => 'online.invite',
                ];
            }

            return $this->present($db, (int) $match['id'], $userId);
        });
        $this->notify($notice);

        return $view;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function acceptInvite(array $user, int $inviteId): array
    {
        $invite = $this->db->fetch(
            'SELECT i.id, i.inviter_id, m.code
             FROM online_invites i
             JOIN online_matches m ON m.id = i.online_match_id
             WHERE i.id = :id AND i.invitee_id = :user AND i.status = \'pending\'',
            ['id' => $inviteId, 'user' => (int) $user['id']]
        );
        if ($invite === null || $invite['code'] === null) {
            throw new OnlineMatchException('Pozvánka už neplatí.', 404);
        }
        $this->assertFriends((int) $user['id'], (int) $invite['inviter_id']);

        return $this->join($user, (string) $invite['code'], '', (int) $invite['id']);
    }

    public function declineInvite(int $userId, int $inviteId): void
    {
        $this->db->update('online_invites', [
            'status' => 'declined',
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ], 'id = :id AND invitee_id = :user AND status = \'pending\'', [
            'id' => $inviteId,
            'user' => $userId,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function incoming(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT i.id, m.public_id, m.code, m.rules, m.kind,
                    u.public_id AS user_public_id, u.username, u.first_name, u.last_name
             FROM online_invites i
             JOIN online_matches m ON m.id = i.online_match_id
             JOIN users u ON u.id = i.inviter_id
             WHERE i.invitee_id = :user AND i.status = \'pending\' AND m.status = \'lobby\' AND m.code IS NOT NULL
             ORDER BY i.id DESC',
            ['user' => $userId]
        );
        $list = [];
        foreach ($rows as $row) {
            $rules = json_decode((string) $row['rules'], true);
            $list[] = [
                'id' => (int) $row['id'],
                'matchId' => (string) $row['public_id'],
                'code' => (string) $row['code'],
                'kind' => (string) $row['kind'],
                'startingScore' => (int) (is_array($rules) ? ($rules['startingScore'] ?? 501) : 501),
                'outRule' => (string) (is_array($rules) ? ($rules['outRule'] ?? 'double') : 'double'),
                'legsToWin' => (int) (is_array($rules) ? ($rules['legsToWin'] ?? 1) : 1),
                'setsToWin' => (int) (is_array($rules) ? ($rules['setsToWin'] ?? 1) : 1),
                'format' => (string) (is_array($rules) ? ($rules['format'] ?? 'firstTo') : 'firstTo'),
                'from' => self::name($row),
                'username' => (string) $row['username'],
            ];
        }

        return $list;
    }

    /**
     * @return list<array{name:string,username:string,status:string}>
     */
    public function lobbyInvites(string $publicId, int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT i.status, u.username, u.first_name, u.last_name, u.public_id
             FROM online_invites i
             JOIN online_matches m ON m.id = i.online_match_id
             JOIN online_match_players p ON p.online_match_id = m.id AND p.user_id = :viewer
             JOIN users u ON u.id = i.invitee_id
             WHERE m.public_id = :id
             ORDER BY i.id',
            ['viewer' => $userId, 'id' => $publicId]
        );
        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'name' => self::name($row),
                'username' => (string) $row['username'],
                'publicId' => (string) $row['public_id'],
                'status' => (string) $row['status'],
            ];
        }

        return $list;
    }

    private function lockUser(Database $db, int $userId): void
    {
        $db->query('SELECT id FROM users WHERE id = :id FOR UPDATE', ['id' => $userId]);
    }

    /** @return array<string, mixed> */
    private function requireMatch(Database $db, string $publicId): array
    {
        $match = $db->fetch('SELECT * FROM online_matches WHERE public_id = :id FOR UPDATE', ['id' => $publicId]);
        if ($match === null) {
            throw new OnlineMatchException('Zápas nenalezen.', 404);
        }

        return $match;
    }

    /** @return array<string, mixed> */
    private function lockId(Database $db, int $id): array
    {
        $match = $db->fetch('SELECT * FROM online_matches WHERE id = :id FOR UPDATE', ['id' => $id]);
        if ($match === null) {
            throw new OnlineMatchException('Zápas nenalezen.', 404);
        }

        return $match;
    }

    /**
     * Lobby po čase a zápas bez šipky déle než tři čtvrtě hodiny se uzavře.
     * Volající to commituje, i když pak vrátí chybu.
     *
     * @param array<string, mixed> $match
     * @return array<string, mixed>
     */
    private function settle(Database $db, array $match): array
    {
        $expired = $match['status'] === 'lobby' && $match['expires_at'] !== null && strtotime((string) $match['expires_at'] . ' UTC') <= time();
        if (!$expired && !$this->stale($match)) {
            return $match;
        }
        $this->markAbandoned($db, (int) $match['id'], (int) $match['seq']);
        if ($match['status'] === 'live') {
            $this->deleteMirrors($db, (int) $match['id']);
        }

        return $this->lockId($db, (int) $match['id']);
    }

    /**
     * Id otevřených zápasů účtu. Zámek se bere až potom, seřazený podle id.
     *
     * @return list<int>
     */
    private function openIds(Database $db, int $userId): array
    {
        return array_map('intval', array_column($db->fetchAll(
            'SELECT m.id
             FROM online_match_players p
             JOIN online_matches m ON m.id = p.online_match_id
             WHERE p.user_id = :user AND m.status IN (\'lobby\', \'live\')
             ORDER BY m.id',
            ['user' => $userId]
        ), 'id'));
    }

    /**
     * Zamkne zápasy od nejmenšího id. Dva vstupy do lobby tak nejdou proti sobě.
     *
     * @param list<int> $ids
     * @return array<int, array<string, mixed>>
     */
    private function lockInOrder(Database $db, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        sort($ids);
        $locked = [];
        foreach ($ids as $id) {
            $row = $db->fetch('SELECT * FROM online_matches WHERE id = :id FOR UPDATE', ['id' => $id]);
            if ($row !== null) {
                $locked[(int) $row['id']] = $row;
            }
        }

        return $locked;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function releaseRows(Database $db, int $userId, array $rows): void
    {
        foreach ($rows as $row) {
            if ($row['status'] === 'live' && !$this->stale($row)) {
                throw new OnlineMatchException('Nejdřív dohraj nebo opusť rozehraný online zápas.', 409, 'busy');
            }
        }
        foreach ($rows as $row) {
            $expired = $row['status'] === 'lobby' && $row['expires_at'] !== null && strtotime((string) $row['expires_at'] . ' UTC') <= time();
            if ($row['status'] === 'live' || $expired || (int) $row['host_user_id'] === $userId) {
                $this->markAbandoned($db, (int) $row['id'], (int) $row['seq']);
                if ($row['status'] === 'live') {
                    $this->deleteMirrors($db, (int) $row['id']);
                }
                continue;
            }
            $this->removePlayer($db, $row, $userId);
        }
    }

    /** @param array<string, mixed> $match */
    private function stale(array $match): bool
    {
        $updated = strtotime((string) $match['updated_at'] . ' UTC');

        return $match['status'] === 'live' && $updated !== false && $updated < time() - self::LIVE_STALE_SECONDS;
    }

    /** @param array<string, mixed> $match */
    private function removePlayer(Database $db, array $match, int $userId): void
    {
        $db->query(
            'DELETE FROM online_match_players WHERE online_match_id = :id AND user_id = :user',
            ['id' => (int) $match['id'], 'user' => $userId]
        );
        $state = $this->state($match);
        $state['players'] = array_values(array_filter(
            $state['players'],
            static fn (mixed $player): bool => is_array($player) && (int) ($player['userId'] ?? 0) !== $userId
        ));
        $db->update('online_matches', [
            'state' => self::json($state),
            'seq' => (int) $match['seq'] + 1,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => (int) $match['id']]);
    }

    private function markAbandoned(Database $db, int $id, int $seq): void
    {
        $db->update('online_matches', [
            'status' => 'abandoned',
            'code' => null,
            'seq' => $seq + 1,
            'finished_at' => null,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $id]);
    }

    private function deleteMirrors(Database $db, int $matchId): void
    {
        $db->query(
            'DELETE FROM matches WHERE online_match_id = :id AND status <> \'deleted\'',
            ['id' => $matchId]
        );
    }

    /**
     * @param array<string, mixed> $match
     * @param array<string, mixed> $state
     */
    private function persist(
        Database $db,
        array $match,
        array $state,
        string $status,
        int $seq,
        int $activeSlot,
        bool $clearCode,
        ?string $finishedAt,
        ?string $startedAt,
    ): void {
        $data = [
            'status' => $status,
            'seq' => $seq,
            'active_slot' => $activeSlot,
            'state' => self::json($state),
            'finished_at' => $finishedAt,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ];
        if ($clearCode) {
            $data['code'] = null;
        }
        if ($startedAt !== null) {
            $data['started_at'] = $startedAt;
            $data['expires_at'] = null;
        }
        $db->update('online_matches', $data, 'id = :id', ['id' => (int) $match['id']]);
    }

    /**
     * @param array<string, mixed> $match
     * @param array{startingScore:int,outRule:string,legsToWin:int,doubleIn:bool} $rules
     * @param array<string, mixed> $state
     */
    private function mirror(Database $db, array $match, array $rules, array $state, string $status): void
    {
        $rows = $db->fetchAll(
            'SELECT id, user_id FROM matches WHERE online_match_id = :id FOR UPDATE',
            ['id' => (int) $match['id']]
        );
        $byUser = [];
        foreach ($rows as $row) {
            $byUser[(int) $row['user_id']] = (int) $row['id'];
        }
        $board = $this->board($rules, $state, $status, (int) $match['active_slot']);
        $now = gmdate('Y-m-d H:i:s');
        $played = $this->played($state);
        $finished = $status === 'finished' ? $this->finishedLogs($rules, $state, $board, $played) : null;
        $darts = array_sum(array_map(static fn (array $player): int => (int) ($player['darts'] ?? 0), $state['players']));
        $title = mb_substr((string) ($state['title'] ?? 'Online'), 0, 160);

        foreach ($state['players'] as $player) {
            if (!is_array($player)) {
                continue;
            }
            $userId = (int) $player['userId'];
            $log = $finished[$userId]['log'] ?? ['board' => $board];
            $columns = [
                'status' => $status,
                'seq' => (int) $match['seq'],
                'kind' => 'online',
                'mode' => 'x01',
                'title' => $title !== '' ? $title : 'Online',
                'player_count' => count($state['players']),
                'has_bot' => 0,
                'is_solo' => 0,
                'result' => $finished[$userId]['result'] ?? 'none',
                'average' => $finished[$userId]['average'] ?? null,
                'darts_thrown' => $darts,
                'play_seconds' => $played,
                'finished_at' => $status === 'finished' ? $now : null,
                'updated_at' => $now,
                'log' => self::json($log),
            ];
            if (isset($byUser[$userId])) {
                $db->update('matches', $columns, 'id = :id', ['id' => $byUser[$userId]]);
                continue;
            }
            for ($attempt = 0; ; $attempt++) {
                try {
                    $db->insert('matches', $columns + [
                        'public_id' => self::publicId(),
                        'user_id' => $userId,
                        'client_id' => (string) $match['public_id'],
                        'online_match_id' => (int) $match['id'],
                        'started_at' => gmdate('Y-m-d H:i:s', is_int($state['startedAt'] ?? null) ? $state['startedAt'] : time()),
                    ]);
                    break;
                } catch (PDOException $e) {
                    if ($e->getCode() !== '23000' || $attempt >= 4) {
                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * @param array{startingScore:int,outRule:string,legsToWin:int,doubleIn:bool} $rules
     * @param array<string, mixed> $state
     * @param array<string, mixed> $board
     * @return array<int, array{log: array<string, mixed>, result: string, average: ?float}>
     */
    private function finishedLogs(array $rules, array $state, array $board, int $played): array
    {
        $started = is_int($state['startedAt'] ?? null) ? $state['startedAt'] : time();
        $finishedAt = time();
        $players = [];
        foreach ($state['players'] as $player) {
            if (!is_array($player)) {
                continue;
            }
            $players[] = [
                'id' => (string) $player['playerId'],
                'kind' => 'guest',
                'name' => (string) $player['name'],
                'username' => (string) ($player['username'] ?? ''),
                'userId' => (int) $player['userId'],
                'bot' => null,
                'handicap' => null,
                'final' => [
                    'remaining' => (int) $player['remaining'],
                    'points' => null,
                    'clockTarget' => null,
                ],
            ];
        }
        $canonical = [
            'status' => 'finished',
            'config' => [
                'mode' => 'x01',
                'format' => $rules['format'],
                'legsToWin' => $rules['legsToWin'],
                'setsToWin' => $rules['setsToWin'],
                'entry' => 'darts',
                'startingScore' => $rules['startingScore'],
                'outRule' => $rules['outRule'],
                'doubleIn' => false,
                'dartLimit' => null,
            ],
            'players' => $players,
            'visits' => $state['visits'],
            'winner' => $state['winner'],
            'bullOffLegs' => [],
            'openingStarter' => 0,
        ];
        $stats = MatchStats::analyze($canonical);
        $title = mb_substr((string) ($state['title'] ?? 'Online'), 0, 160);
        $logs = [];
        foreach ($state['players'] as $player) {
            if (!is_array($player)) {
                continue;
            }
            $userId = (int) $player['userId'];
            $logged = [];
            $owner = null;
            foreach ($stats['players'] as $statPlayer) {
                $statPlayer['kind'] = (int) ($statPlayer['userId'] ?? 0) === $userId ? 'owner' : 'friend';
                if ($statPlayer['kind'] === 'owner') {
                    $owner = $statPlayer;
                }
                $logged[] = $statPlayer;
            }
            $logs[$userId] = [
                'result' => is_array($owner) ? (string) ($owner['result'] ?? 'none') : 'none',
                'average' => is_array($owner) ? ($owner['stats']['average'] ?? null) : null,
                'log' => [
                    'version' => 1,
                    'status' => 'finished',
                    'title' => $title !== '' ? $title : 'Online',
                    'startedAt' => gmdate('c', $started),
                    'finishedAt' => gmdate('c', $finishedAt),
                    'playSeconds' => $played,
                    'config' => $canonical['config'],
                    'ghost' => null,
                    'openingStarter' => 0,
                    'winner' => $state['winner'],
                    'players' => $logged,
                    'legs' => $stats['legs'],
                    'visits' => $stats['visits'],
                    'app' => ['version' => null, 'device' => null],
                    'raw' => null,
                    'board' => $board,
                ],
            ];
        }

        return $logs;
    }

    /**
     * @param array{startingScore:int,outRule:string,legsToWin:int,doubleIn:bool} $rules
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function board(array $rules, array $state, string $status, int $active): array
    {
        $openLabels = array_map(static fn (mixed $dart): string => is_array($dart) ? (string) ($dart['label'] ?? '') : '', $state['visit'] ?? []);
        $players = [];
        foreach ($state['players'] as $index => $player) {
            if (!is_array($player)) {
                continue;
            }
            $open = $index === $active ? count($openLabels) : 0;
            $darts = max(0, (int) $player['darts'] - $open);
            $players[] = [
                'name' => (string) $player['name'],
                'bot' => false,
                'score' => (string) (int) $player['remaining'],
                'legs' => (int) $player['legs'],
                'sets' => (int) ($player['sets'] ?? 0),
                'average' => $darts > 0 ? round((int) $player['points'] / $darts * 3, 2) : 0,
                'darts' => $darts,
                'last' => $player['last'],
                'checkout' => '',
                'setup' => '',
                'setupLeave' => '',
                'out' => $rules['outRule'],
                'marks' => new \stdClass(),
            ];
        }
        $visits = [];
        foreach (array_slice(is_array($state['visits'] ?? null) ? $state['visits'] : [], -1500) as $visit) {
            if (!is_array($visit)) {
                continue;
            }
            $visits[] = [
                'player' => (int) ($visit['player'] ?? 0),
                'leg' => (int) ($visit['leg'] ?? 1),
                'darts' => array_column(is_array($visit['darts'] ?? null) ? $visit['darts'] : [], 'label'),
                'score' => (int) ($visit['score'] ?? 0),
                'bust' => ($visit['bust'] ?? false) === true,
                'checkout' => ($visit['checkout'] ?? false) === true,
            ];
        }
        $title = trim((string) ($state['title'] ?? ''));

        return [
            'mode' => 'x01',
            'title' => $title !== '' ? $title : 'Online',
            'leg' => (int) ($state['leg'] ?? 1),
            'set' => (int) ($state['set'] ?? 1),
            'legsToWin' => $rules['legsToWin'],
            'setsToWin' => $rules['setsToWin'],
            'active' => $active,
            'winner' => is_int($state['winner'] ?? null) ? $state['winner'] : null,
            'legWinner' => is_int($state['legWinner'] ?? null) ? $state['legWinner'] : null,
            'bullOff' => false,
            'legSlots' => 0,
            'setSlots' => 0,
            'paused' => false,
            'played' => $this->played($state),
            'running' => $status === 'live',
            'numbers' => [],
            'players' => $players,
            'currentDarts' => $openLabels,
            'visits' => $visits,
        ];
    }

    /** @param array<string, mixed> $state */
    private function played(array $state): int
    {
        $started = $state['startedAt'] ?? null;

        return is_int($started) ? max(0, time() - $started) : 0;
    }

    /** @return array<string, mixed> */
    private function present(Database $db, int $matchId, int $userId): array
    {
        $match = $this->lockId($db, $matchId);

        return $this->view($match, $this->players($db, $matchId), $this->rules($match), $this->state($match), $userId);
    }

    /**
     * @param array<string, mixed> $match
     * @param list<array<string, mixed>> $players
     * @param array{startingScore:int,outRule:string,legsToWin:int,doubleIn:bool} $rules
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function view(array $match, array $players, array $rules, array $state, int $viewerId): array
    {
        $scores = [];
        foreach ($state['players'] as $player) {
            if (is_array($player)) {
                $scores[(int) ($player['userId'] ?? 0)] = $player;
            }
        }
        $mine = $this->member($players, $viewerId);
        $listed = [];
        foreach ($players as $player) {
            $score = $scores[(int) $player['user_id']] ?? null;
            $listed[] = [
                'slot' => (int) $player['slot'],
                'id' => (string) $player['user_public_id'],
                'name' => is_array($score) ? (string) $score['name'] : self::name($player),
                'username' => (string) $player['username'],
                'photo' => self::photo($player),
                'ready' => (int) $player['ready'] === 1,
                'you' => (int) $player['user_id'] === $viewerId,
                'remaining' => (int) (is_array($score) ? $score['remaining'] : $rules['startingScore']),
                'legs' => (int) (is_array($score) ? $score['legs'] : 0),
                'sets' => (int) (is_array($score) ? ($score['sets'] ?? 0) : 0),
                'rating' => (int) $player['rating'],
            ];
        }
        $slot = $mine === null ? null : (int) $mine['slot'];
        $winner = is_int($state['winner'] ?? null) ? $state['winner'] : null;
        $change = $slot !== null && is_array($state['rating'][$slot] ?? null) ? (int) $state['rating'][$slot]['delta'] : null;

        return [
            'id' => (string) $match['public_id'],
            'code' => $match['code'] !== null ? (string) $match['code'] : null,
            'kind' => (string) ($match['kind'] ?? 'classic'),
            'visibility' => (string) ($match['visibility'] ?? 'private'),
            'ratingChange' => $change,
            'status' => (string) $match['status'],
            'seq' => (int) $match['seq'],
            'yourSlot' => $slot,
            'activeSlot' => (int) $match['active_slot'],
            'yourTurn' => $slot !== null && $match['status'] === 'live' && (int) $match['active_slot'] === $slot && $winner === null,
            'member' => $mine !== null,
            'hasPassword' => $match['password_hash'] !== null,
            'winner' => $winner,
            'startedAt' => is_int($state['startedAt'] ?? null) ? gmdate('c', $state['startedAt']) : null,
            'rules' => $rules,
            'players' => $listed,
            'state' => $this->board($rules, $state, (string) $match['status'], (int) $match['active_slot']),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function players(Database $db, int $matchId): array
    {
        return $db->fetchAll(
            'SELECT p.user_id, p.slot, p.ready, u.public_id AS user_public_id, u.username, u.first_name, u.last_name,
                    u.avatar_path, u.profile_revision,
                    COALESCE(r.rating, 1000) AS rating
             FROM online_match_players p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN online_ratings r ON r.user_id = p.user_id
             WHERE p.online_match_id = :id
             ORDER BY p.slot',
            ['id' => $matchId]
        );
    }

    /**
     * @param list<array<string, mixed>> $players
     * @return array<string, mixed>|null
     */
    private function member(array $players, int $userId): ?array
    {
        foreach ($players as $player) {
            if ((int) $player['user_id'] === $userId) {
                return $player;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $players
     * @return array<string, mixed>
     */
    private function requireMember(array $players, int $userId): array
    {
        $member = $this->member($players, $userId);
        if ($member === null) {
            throw new OnlineMatchException('Zápas nenalezen.', 404);
        }

        return $member;
    }

    /** @param array<string, mixed> $match
     *  @return array{startingScore:int,outRule:string,legsToWin:int,doubleIn:bool}
     */
    private function rules(array $match): array
    {
        $rules = json_decode((string) $match['rules'], true);
        if (!is_array($rules) || !isset($rules['startingScore'], $rules['outRule'], $rules['legsToWin'])) {
            throw new RuntimeException('Rozbitá pravidla zápasu.');
        }

        return [
            'startingScore' => (int) $rules['startingScore'],
            'outRule' => (string) $rules['outRule'],
            'legsToWin' => (int) $rules['legsToWin'],
            'setsToWin' => max(1, (int) ($rules['setsToWin'] ?? 1)),
            'format' => ($rules['format'] ?? 'firstTo') === 'bestOf' ? 'bestOf' : 'firstTo',
            'doubleIn' => false,
        ];
    }

    /**
     * @param array<string, mixed> $match
     * @return array<string, mixed>
     */
    private function state(array $match): array
    {
        $state = json_decode((string) $match['state'], true);
        if (!is_array($state) || !is_array($state['players'] ?? null)) {
            throw new RuntimeException('Rozbitý stav zápasu.');
        }
        $state['visit'] = is_array($state['visit'] ?? null) ? $state['visit'] : [];
        $state['visits'] = is_array($state['visits'] ?? null) ? $state['visits'] : [];

        return $state;
    }

    /** @param array<string, mixed> $user
     *  @return array<string, mixed>
     */
    private function player(array $user, int $remaining): array
    {
        return [
            'userId' => (int) $user['id'],
            'publicId' => (string) $user['public_id'],
            'playerId' => self::uuid(),
            'name' => self::name($user),
            'username' => (string) $user['username'],
            'remaining' => $remaining,
            'legs' => 0,
            'sets' => 0,
            'darts' => 0,
            'points' => 0,
            'last' => null,
        ];
    }

    /** @param array<string, mixed> $match */
    private function passwordOk(array $match, string $password): bool
    {
        $hash = $match['password_hash'];
        if ($hash === null) {
            return true;
        }

        return is_string($hash) && password_verify($password, $hash);
    }

    /** Platná pozvánka přeskočí heslo. Musí patřit tomuto hráči a tomuto zápasu. */
    private function trustedInvite(Database $db, int $inviteId, int $userId, int $matchId): bool
    {
        if ($inviteId < 1) {
            return false;
        }
        $row = $db->fetch(
            'SELECT id FROM online_invites
             WHERE id = :id AND invitee_id = :user AND online_match_id = :match AND status = \'pending\'
             FOR UPDATE',
            ['id' => $inviteId, 'user' => $userId, 'match' => $matchId]
        );

        return $row !== null;
    }

    private function acceptInviteRow(Database $db, int $inviteId): void
    {
        $db->update('online_invites', [
            'status' => 'accepted',
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $inviteId]);
    }

    private function assertFriends(int $userId, int $friendId): void
    {
        [$low, $high] = $userId < $friendId ? [$userId, $friendId] : [$friendId, $userId];
        $row = $this->db->fetch(
            'SELECT id FROM friendships WHERE user_low = :low AND user_high = :high AND status = \'accepted\' LIMIT 1',
            ['low' => $low, 'high' => $high]
        );
        if ($row === null) {
            throw new OnlineMatchException('Pozvat jde jen přijatého kamaráda.', 422);
        }
    }

    private function burnPasswordTime(string $password): void
    {
        password_verify($password, self::DUMMY_HASH);
    }

    private function assertNotLocked(Database $db, int $userId, string $code): void
    {
        $row = $db->fetch(
            'SELECT locked_until FROM online_join_attempts WHERE user_id = :user AND code = :code FOR UPDATE',
            ['user' => $userId, 'code' => $code]
        );
        if ($row !== null && $row['locked_until'] !== null && strtotime((string) $row['locked_until'] . ' UTC') > time()) {
            throw new OnlineMatchException('Moc pokusů o vstup. Zkus to znovu za chvíli.', 429, 'locked');
        }
    }

    private function failJoin(Database $db, int $userId, string $code): void
    {
        $row = $db->fetch(
            'SELECT failures, locked_until FROM online_join_attempts WHERE user_id = :user AND code = :code FOR UPDATE',
            ['user' => $userId, 'code' => $code]
        );
        $failures = (int) ($row['failures'] ?? 0);
        if ($row !== null && $row['locked_until'] !== null && strtotime((string) $row['locked_until'] . ' UTC') <= time()) {
            $failures = 0;
        }
        $failures++;
        $locked = $failures >= self::JOIN_MAX_FAILURES ? gmdate('Y-m-d H:i:s', time() + self::JOIN_LOCK_SECONDS) : null;
        $now = gmdate('Y-m-d H:i:s');
        if ($row === null) {
            $db->insert('online_join_attempts', [
                'user_id' => $userId,
                'code' => $code,
                'failures' => $failures,
                'locked_until' => $locked,
                'updated_at' => $now,
            ]);

            return;
        }
        $db->update('online_join_attempts', [
            'failures' => $failures,
            'locked_until' => $locked,
            'updated_at' => $now,
        ], 'user_id = :user AND code = :code', ['user' => $userId, 'code' => $code]);
    }

    private function clearAttempts(Database $db, int $userId, string $code): void
    {
        $db->query('DELETE FROM online_join_attempts WHERE user_id = :user AND code = :code', [
            'user' => $userId,
            'code' => $code,
        ]);
    }

    /** @param array{userId:int,title:string,body:string,type:string}|null $notice */
    private function notify(?array $notice): void
    {
        if ($notice === null || $notice['userId'] < 1) {
            return;
        }
        (new PushService($this->db))->send($notice['userId'], $notice['title'], $notice['body'], $notice['type']);
    }

    /** @param array<string, mixed> $value */
    private static function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $user */
    private static function name(array $user): string
    {
        $name = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
        $name = preg_replace('/\s+/u', ' ', $name) ?? '';
        if ($name === '') {
            $name = trim((string) ($user['username'] ?? ''));
        }

        return mb_substr($name !== '' ? $name : 'Hráč', 0, 40);
    }

    /** @param array<string, mixed> $user */
    private static function photo(array $user): ?string
    {
        $path = (string) ($user['avatar_path'] ?? '');

        return $path === '' ? null : '/' . $path . '?v=' . (int) ($user['profile_revision'] ?? 1);
    }

    private static function hash(string $password): ?string
    {
        if ($password === '') {
            return null;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!is_string($hash)) {
            throw new RuntimeException('Heslo nejde uložit.');
        }

        return $hash;
    }

    private static function publicId(): string
    {
        return bin2hex(random_bytes(12));
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
    }

    private static function lobbyCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }
}
