-- Jednorázově pro existující databázi Dartiqo.
-- Společný online zápas a označení jeho kopií v matches.

CREATE TABLE IF NOT EXISTS online_matches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(24) NOT NULL,
    host_user_id BIGINT UNSIGNED NOT NULL,
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
    CONSTRAINT online_matches_host_fk FOREIGN KEY (host_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS online_join_attempts (
    user_id BIGINT UNSIGNED NOT NULL,
    code CHAR(6) NOT NULL,
    failures TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, code),
    CONSTRAINT online_join_attempts_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE matches
    ADD COLUMN kind ENUM('local', 'online') NOT NULL DEFAULT 'local' AFTER is_solo,
    ADD COLUMN online_match_id BIGINT UNSIGNED NULL AFTER kind,
    ADD KEY matches_online_index (online_match_id),
    ADD CONSTRAINT matches_online_fk FOREIGN KEY (online_match_id) REFERENCES online_matches (id) ON DELETE SET NULL;
