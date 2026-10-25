-- Jednorázově pro existující databázi Dartiqo.
-- Pozvánka kamaráda do online lobby.

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
