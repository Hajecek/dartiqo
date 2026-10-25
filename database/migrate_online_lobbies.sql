-- Jednorázově pro existující databázi Dartiqo.
-- Veřejná a uzavřená lobby, ranked zápasy a jejich rating.

ALTER TABLE online_matches
    ADD COLUMN kind ENUM('classic', 'ranked') NOT NULL DEFAULT 'classic' AFTER host_user_id,
    ADD COLUMN visibility ENUM('public', 'private') NOT NULL DEFAULT 'private' AFTER kind,
    ADD KEY online_matches_list_index (status, visibility, created_at);

CREATE TABLE IF NOT EXISTS online_ratings (
    user_id BIGINT UNSIGNED NOT NULL,
    rating SMALLINT UNSIGNED NOT NULL DEFAULT 1000,
    played INT UNSIGNED NOT NULL DEFAULT 0,
    wins INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id),
    CONSTRAINT online_ratings_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
