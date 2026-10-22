CREATE DATABASE IF NOT EXISTS dartiqo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dartiqo;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    -- Kolace *_ci zajistí, že "Petr" a "petr" jsou stejné username/e-mail
    username VARCHAR(30) NOT NULL,
    email VARCHAR(191) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    -- Relativní cesta k souboru, např. uploads/avatars/ab12cd.webp
    avatar_path VARCHAR(255) NULL,

    plan ENUM('free', 'pro') NOT NULL DEFAULT 'free',
    plan_interval ENUM('month', 'year') NULL,
    -- NULL = plán bez expirace (Free)
    plan_expires_at DATETIME NULL,

    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
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

-- Rozehraný zápas z mobilu, který web ukazuje živě
CREATE TABLE IF NOT EXISTS live_matches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    -- UUID zápasu z aplikace
    client_id CHAR(36) NOT NULL,
    status ENUM('live', 'finished', 'abandoned') NOT NULL DEFAULT 'live',
    -- Pořadí aktualizace z aplikace; starší požadavek nepřepíše novější stav
    seq INT UNSIGNED NOT NULL DEFAULT 0,
    state MEDIUMTEXT NOT NULL,
    started_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    finished_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY live_matches_client_unique (user_id, client_id),
    KEY live_matches_recent_index (user_id, updated_at),
    CONSTRAINT live_matches_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
