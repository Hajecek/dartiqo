CREATE DATABASE IF NOT EXISTS dartiqo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dartiqo;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    -- Náhodné veřejné id do odkazů (96 bitů); interní id se ven neukazuje
    public_id CHAR(24) NOT NULL,
    -- Kolace *_ci zajistí, že "Petr" a "petr" jsou stejné username/e-mail
    username VARCHAR(30) NOT NULL,
    email VARCHAR(191) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    -- Relativní cesta k souboru, např. uploads/avatars/ab12cd.webp
    avatar_path VARCHAR(255) NULL,
    -- Monotónní verze profilu. Web i aplikace podle ní poznají realtime změnu.
    profile_revision INT UNSIGNED NOT NULL DEFAULT 1,

    plan ENUM('free', 'pro') NOT NULL DEFAULT 'free',
    plan_interval ENUM('month', 'year') NULL,
    -- NULL = plán bez expirace (Free)
    plan_expires_at DATETIME NULL,

    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    -- public = kamarádi vidí statistiky, private = kamarádům se neukáže nic ze hry
    visibility ENUM('public', 'private') NOT NULL DEFAULT 'public',
    email_verified_at DATETIME NULL,

    -- SHA-256 tokenu pro zapomenuté heslo; samotný token jde jen e-mailem
    reset_token_hash CHAR(64) NULL,
    reset_expires_at DATETIME NULL,

    -- SHA-256 tokenu z cookie "Zapamatovat si mě"
    remember_token_hash CHAR(64) NULL,

    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY users_public_unique (public_id),
    UNIQUE KEY users_username_unique (username),
    UNIQUE KEY users_email_unique (email),
    UNIQUE KEY users_reset_token_unique (reset_token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Přihlášení z mobilní aplikace. Samotný token zná jen zařízení.
CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    name VARCHAR(100) NOT NULL,
    last_used_at DATETIME NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY api_tokens_hash_unique (token_hash),
    KEY api_tokens_user_index (user_id),
    CONSTRAINT api_tokens_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FCM token zařízení. Stejný model jako Privofit: hash je unikátní, starý token relace se zneplatní.
-- Dartiqo nemá api_devices, zařízení je přihlášení v api_tokens.
CREATE TABLE IF NOT EXISTS fcm_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    api_token_id BIGINT UNSIGNED NULL,
    token VARCHAR(512) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    environment ENUM('development', 'production') NOT NULL DEFAULT 'production',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NULL,
    invalidated_at DATETIME NULL,
    invalid_reason VARCHAR(255) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY fcm_tokens_hash_unique (token_hash),
    KEY fcm_tokens_user_active_index (user_id, is_active),
    KEY fcm_tokens_api_token_active_index (api_token_id, is_active),
    CONSTRAINT fcm_tokens_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fcm_tokens_api_token_fk FOREIGN KEY (api_token_id) REFERENCES api_tokens (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Společný online zápas. Tah, skóre a lobby žijí tady, ne v řádcích jednotlivých účtů.
-- Kód se po skončení uvolní (NULL), aby ho šlo použít znovu.
CREATE TABLE IF NOT EXISTS online_matches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(24) NOT NULL,
    host_user_id BIGINT UNSIGNED NOT NULL,
    kind ENUM('classic', 'ranked') NOT NULL DEFAULT 'classic',
    visibility ENUM('public', 'private') NOT NULL DEFAULT 'private',
    code CHAR(6) NULL,
    password_hash VARCHAR(255) NULL,
    status ENUM('lobby', 'live', 'finished', 'abandoned') NOT NULL,
    rules JSON NOT NULL,
    active_slot TINYINT UNSIGNED NOT NULL DEFAULT 0,
    seq INT UNSIGNED NOT NULL DEFAULT 0,
    state JSON NOT NULL,
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY online_matches_public_unique (public_id),
    UNIQUE KEY online_matches_code_unique (code),
    KEY online_matches_host_index (host_user_id, status),
    KEY online_matches_list_index (status, visibility, created_at),
    CONSTRAINT online_matches_host_fk FOREIGN KEY (host_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rating z ranked zápasů. Účet bez řádku má výchozích 1000.
CREATE TABLE IF NOT EXISTS online_ratings (
    user_id BIGINT UNSIGNED NOT NULL,
    rating SMALLINT UNSIGNED NOT NULL DEFAULT 1000,
    played INT UNSIGNED NOT NULL DEFAULT 0,
    wins INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id),
    CONSTRAINT online_ratings_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Slot 0 je hostitel, slot 1 host. V jednom zápase je každý účet jen jednou.
CREATE TABLE IF NOT EXISTS online_match_players (
    online_match_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    slot TINYINT UNSIGNED NOT NULL,
    ready TINYINT(1) NOT NULL DEFAULT 0,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (online_match_id, user_id),
    UNIQUE KEY online_match_players_slot_unique (online_match_id, slot),
    KEY online_match_players_user_index (user_id),
    CONSTRAINT online_match_players_match_fk FOREIGN KEY (online_match_id) REFERENCES online_matches (id) ON DELETE CASCADE,
    CONSTRAINT online_match_players_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Špatné heslo do lobby. Po pěti pokusech je dvojice účet + kód na chvíli zamčená.
CREATE TABLE IF NOT EXISTS online_join_attempts (
    user_id BIGINT UNSIGNED NOT NULL,
    code CHAR(6) NOT NULL,
    failures TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, code),
    CONSTRAINT online_join_attempts_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pozvánka kamaráda do lobby. Přijetí ho posadí do zápasu bez hesla.
CREATE TABLE IF NOT EXISTS online_invites (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    online_match_id BIGINT UNSIGNED NOT NULL,
    inviter_id BIGINT UNSIGNED NOT NULL,
    invitee_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'declined') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY online_invites_pair_unique (online_match_id, invitee_id),
    KEY online_invites_invitee_index (invitee_id, status),
    CONSTRAINT online_invites_match_fk FOREIGN KEY (online_match_id) REFERENCES online_matches (id) ON DELETE CASCADE,
    CONSTRAINT online_invites_inviter_fk FOREIGN KEY (inviter_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT online_invites_invitee_fk FOREIGN KEY (invitee_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zápas z mobilní aplikace, včetně právě hraného. Jeden řádek = jeden zápas jednoho účtu.
-- status live web ukazuje živě. Sloupce slouží k výpisům, průběh a živý snímek jsou v log.
-- Online zápas má kind online a odkaz na společný řádek. Skóre se do něj jen zrcadlí.
CREATE TABLE IF NOT EXISTS matches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    -- Náhodné veřejné id do odkazů (96 bitů); interní id se ven neukazuje
    public_id CHAR(24) NOT NULL,
    -- Účet, ze kterého aplikace zápas nahrála
    user_id BIGINT UNSIGNED NOT NULL,
    -- UUID zápasu z aplikace; opakované nahrání zápas jen přepíše
    client_id CHAR(36) NOT NULL,
    status ENUM('live', 'finished', 'abandoned', 'deleted') NOT NULL,
    -- Pořadí živé aktualizace z aplikace; starší požadavek nepřepíše novější snímek
    seq INT UNSIGNED NOT NULL DEFAULT 0,
    mode ENUM('x01', 'cricket', 'aroundClock', 'countUp') NOT NULL,
    title VARCHAR(160) NOT NULL,
    player_count TINYINT UNSIGNED NOT NULL,
    has_bot TINYINT(1) NOT NULL DEFAULT 0,
    -- Sólo hra se nepočítá do výher a proher
    is_solo TINYINT(1) NOT NULL DEFAULT 0,
    -- local = zápas z jednoho telefonu, online = kopie společného zápasu
    kind ENUM('local', 'online') NOT NULL DEFAULT 'local',
    online_match_id BIGINT UNSIGNED NULL,
    -- Výsledek majitele účtu
    result ENUM('won', 'lost', 'draw', 'none') NOT NULL DEFAULT 'none',
    -- Tříšipkový průměr majitele (X01, Count Up)
    average DECIMAL(6,2) NULL,
    darts_thrown INT UNSIGNED NOT NULL DEFAULT 0,
    play_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    -- Živý snímek je v board. Po dohrání je tu i celý průběh: pravidla, hráči, legy a návštěvy
    log JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY matches_public_unique (public_id),
    UNIQUE KEY matches_client_unique (user_id, client_id),
    KEY matches_user_recent_index (user_id, status, started_at),
    KEY matches_live_index (user_id, status, updated_at),
    KEY matches_online_index (online_match_id),
    CONSTRAINT matches_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT matches_online_fk FOREIGN KEY (online_match_id) REFERENCES online_matches (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Žádost o přátelství. Dvojice účtů je v tabulce jen jednou, user_low má menší id.
CREATE TABLE IF NOT EXISTS friendships (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_low BIGINT UNSIGNED NOT NULL,
    user_high BIGINT UNSIGNED NOT NULL,
    requester_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'declined') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY friendships_pair_unique (user_low, user_high),
    KEY friendships_low_index (user_low, status),
    KEY friendships_high_index (user_high, status),
    CONSTRAINT friendships_low_fk FOREIGN KEY (user_low) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT friendships_high_fk FOREIGN KEY (user_high) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT friendships_requester_fk FOREIGN KEY (requester_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
