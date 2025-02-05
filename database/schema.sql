-- Tworzenie bazy danych
CREATE DATABASE IF NOT EXISTS breathtime CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE breathtime;

-- Tabela użytkowników
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    verification_token VARCHAR(64) NULL,
    status ENUM('unverified', 'active', 'suspended') DEFAULT 'unverified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    role ENUM('user', 'admin') DEFAULT 'user'
) ENGINE=InnoDB;

-- Tabela darowizn
CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PLN',
    payment_id VARCHAR(255) NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela newslettera
CREATE TABLE IF NOT EXISTS newsletter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    is_confirmed BOOLEAN DEFAULT FALSE,
    confirmation_token VARCHAR(64) NULL,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

-- Tabela odwiedzin
CREATE TABLE IF NOT EXISTS visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    page_url VARCHAR(255) NOT NULL,
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_id VARCHAR(32) NULL,
    referrer_url VARCHAR(255) NULL
) ENGINE=InnoDB;

-- Tabela pobrań
CREATE TABLE IF NOT EXISTS downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    file_name VARCHAR(255) NOT NULL,
    download_token VARCHAR(64) NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    is_expired BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Indeksy
CREATE INDEX idx_visits_ip ON visits(ip_address);
CREATE INDEX idx_visits_session ON visits(session_id);
CREATE INDEX idx_downloads_token ON downloads(download_token);
CREATE INDEX idx_newsletter_email ON newsletter(email);
CREATE INDEX idx_donations_status ON donations(payment_status);

-- Użytkownik bazy danych
CREATE USER IF NOT EXISTS 'breathtime_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON breathtime.* TO 'breathtime_user'@'localhost';
FLUSH PRIVILEGES;
