ALTER TABLE petitions
ADD COLUMN category VARCHAR(50) DEFAULT 'other';

-- Dodaj podstawowe kategorie
UPDATE petitions SET category = 'environment' WHERE title LIKE '%BreathTime%';
