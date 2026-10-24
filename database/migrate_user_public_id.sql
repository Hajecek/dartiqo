-- Jednorázově pro existující databázi Dartiqo.
-- Sloupec nejdřív smí být prázdný. Každému řádku doplň unikátní public_id
-- (24 hex znaků, stejně jako UserRepository::newPublicId) a teprve potom:
-- ALTER TABLE users MODIFY public_id CHAR(24) NOT NULL;
ALTER TABLE users
    ADD COLUMN public_id CHAR(24) NULL AFTER id,
    ADD UNIQUE KEY users_public_unique (public_id);
