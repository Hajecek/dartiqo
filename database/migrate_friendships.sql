-- Jednorázově pro existující databázi Dartiqo.
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
