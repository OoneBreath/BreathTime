ALTER TABLE users 
ADD COLUMN status ENUM('unverified', 'active', 'suspended') DEFAULT 'unverified';

ALTER TABLE users 
CHANGE COLUMN password password_hash VARCHAR(255) NOT NULL;
