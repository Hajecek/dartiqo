-- Jednorázově pro existující databázi Dartiqo.
ALTER TABLE users
    ADD COLUMN profile_revision INT UNSIGNED NOT NULL DEFAULT 1 AFTER avatar_path;
