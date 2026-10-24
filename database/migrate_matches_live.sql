-- Jednorázově pro existující databázi Dartiqo.
-- Živé zápasy přestěhuje do matches a tabulku live_matches zruší.

ALTER TABLE matches
    MODIFY status ENUM('live', 'finished', 'abandoned') NOT NULL,
    ADD COLUMN seq INT UNSIGNED NOT NULL DEFAULT 0 AFTER status,
    ADD KEY matches_live_index (user_id, status, updated_at);

INSERT INTO matches (
    public_id, user_id, client_id, status, seq, mode, title, player_count,
    has_bot, is_solo, result, darts_thrown, play_seconds, started_at, finished_at, log, updated_at
)
SELECT
    SUBSTRING(REPLACE(UUID(), '-', ''), 1, 24),
    lm.user_id,
    lm.client_id,
    lm.status,
    lm.seq,
    CASE JSON_UNQUOTE(JSON_EXTRACT(lm.state, '$.mode'))
        WHEN 'cricket' THEN 'cricket'
        WHEN 'aroundClock' THEN 'aroundClock'
        WHEN 'countUp' THEN 'countUp'
        ELSE 'x01'
    END,
    LEFT(COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(lm.state, '$.title')), ''), 'Zápas'), 160),
    GREATEST(1, COALESCE(JSON_LENGTH(JSON_EXTRACT(lm.state, '$.players')), 1)),
    0,
    IF(COALESCE(JSON_LENGTH(JSON_EXTRACT(lm.state, '$.players')), 1) < 2, 1, 0),
    'none',
    0,
    LEAST(604800, GREATEST(0, CAST(COALESCE(JSON_EXTRACT(lm.state, '$.played'), 0) AS UNSIGNED))),
    lm.started_at,
    lm.finished_at,
    JSON_OBJECT('board', JSON_EXTRACT(lm.state, '$')),
    lm.updated_at
FROM live_matches lm
LEFT JOIN matches m ON m.user_id = lm.user_id AND m.client_id = lm.client_id
WHERE m.id IS NULL AND lm.status <> 'abandoned';

UPDATE matches m
INNER JOIN live_matches lm ON lm.user_id = m.user_id AND lm.client_id = m.client_id
SET
    m.log = JSON_SET(m.log, '$.board', JSON_EXTRACT(lm.state, '$')),
    m.seq = GREATEST(m.seq, lm.seq),
    m.updated_at = IF(lm.updated_at > m.updated_at, lm.updated_at, m.updated_at)
WHERE lm.status <> 'abandoned';

DROP TABLE live_matches;
