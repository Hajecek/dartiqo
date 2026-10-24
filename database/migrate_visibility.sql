-- Jednorázově pro existující databázi Dartiqo.
ALTER TABLE users
    ADD COLUMN visibility ENUM('public', 'private') NOT NULL DEFAULT 'public' AFTER status;
