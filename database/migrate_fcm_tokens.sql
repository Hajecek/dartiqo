-- Jednorázově pro existující databázi Dartiqo.
-- FCM token zařízení, stejný účel jako Privofit (fcm_tokens), navázaný na api_tokens.
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
