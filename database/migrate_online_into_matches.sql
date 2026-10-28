-- Jednorázově. Online zápas se ukládá do matches, ne do vlastních tabulek.
-- Dohrané online zápasy v matches už jsou. Otevřená lobby v tomhle kroku neběží.

ALTER TABLE users
    ADD COLUMN rating SMALLINT UNSIGNED NOT NULL DEFAULT 1000 AFTER visibility;

ALTER TABLE matches
    MODIFY status ENUM('lobby', 'live', 'finished', 'abandoned', 'deleted') NOT NULL,
    ADD COLUMN guest_user_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD COLUMN ranked TINYINT(1) NOT NULL DEFAULT 0 AFTER kind,
    ADD COLUMN visibility ENUM('public', 'private') NULL AFTER ranked,
    ADD COLUMN code CHAR(6) NULL AFTER visibility,
    ADD COLUMN password_hash VARCHAR(255) NULL AFTER code,
    ADD COLUMN active_slot TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER password_hash,
    ADD COLUMN expires_at DATETIME NULL AFTER finished_at,
    ADD COLUMN rules JSON NULL AFTER log,
    ADD COLUMN state JSON NULL AFTER rules,
    ADD UNIQUE KEY matches_code_unique (code),
    ADD KEY matches_guest_index (guest_user_id, status),
    ADD KEY matches_lobby_index (status, visibility, expires_at),
    ADD CONSTRAINT matches_guest_fk FOREIGN KEY (guest_user_id) REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE matches DROP FOREIGN KEY matches_online_fk;
ALTER TABLE matches DROP INDEX matches_online_index;
ALTER TABLE matches DROP COLUMN online_match_id;

DELETE FROM online_invites;
ALTER TABLE online_invites DROP FOREIGN KEY online_invites_match_fk;
ALTER TABLE online_invites CHANGE online_match_id match_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE online_invites
    ADD CONSTRAINT online_invites_match_fk FOREIGN KEY (match_id) REFERENCES matches (id) ON DELETE CASCADE;

DROP TABLE online_match_players;
DROP TABLE online_matches;
