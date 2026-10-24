-- Jednorázově pro existující databázi Dartiqo.
-- Smazaný zápas zůstane v tabulce a počítá se mezi dokončené.

ALTER TABLE matches
    MODIFY status ENUM('live', 'finished', 'abandoned', 'deleted') NOT NULL;
