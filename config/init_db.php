<?php
// Wczytanie konfiguracji
$config = require_once 'database.php';

try {
    // Połączenie z MySQL (bez wybierania bazy danych)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'onebreath_baza');
    define('DB_USER', 'onebreath_info');
    define('DB_PASS', 'mZkdNfUaseEn');

    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Ustawienie trybu błędów na wyjątki
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tworzenie bazy danych
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['dbname']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Baza danych {$config['dbname']} została utworzona lub już istnieje.\n";
    
    // Wybór bazy danych
    $pdo->exec("USE {$config['dbname']}");
    
    // Tworzenie tabeli zakupów
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        charge_id VARCHAR(255) NOT NULL,
        download_token VARCHAR(255) NOT NULL,
        amount INT NOT NULL,
        timestamp DATETIME NOT NULL,
        is_valid BOOLEAN DEFAULT 1,
        expiry_date DATETIME,
        download_count INT DEFAULT 0,
        INDEX idx_token (download_token),
        INDEX idx_email (email)
    ) ENGINE=InnoDB");
    echo "Tabela 'purchases' została utworzona.\n";
    
    // Tworzenie tabeli pobrań
    $pdo->exec("CREATE TABLE IF NOT EXISTS downloads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(255) NOT NULL,
        format VARCHAR(10) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        timestamp DATETIME NOT NULL,
        INDEX idx_token (token),
        INDEX idx_timestamp (timestamp)
    ) ENGINE=InnoDB");
    echo "Tabela 'downloads' została utworzona.\n";
    
    // Tworzenie tabeli logów
    $pdo->exec("CREATE TABLE IF NOT EXISTS logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        message TEXT,
        data JSON,
        timestamp DATETIME NOT NULL,
        INDEX idx_type (type),
        INDEX idx_timestamp (timestamp)
    ) ENGINE=InnoDB");
    echo "Tabela 'logs' została utworzona.\n";

    // Tworzenie tabeli users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            status ENUM('unverified', 'active', 'banned') DEFAULT 'unverified',
            role ENUM(
                'volunteer',
                'educator',
                'monitor',
                'foundation',
                'tech_partner',
                'edu_institution',
                'expert',
                'strategic_partner',
                'ambassador'
            ) DEFAULT NULL,
            role_verified BOOLEAN DEFAULT FALSE,
            role_application_date DATETIME DEFAULT NULL,
            role_verification_date DATETIME DEFAULT NULL,
            role_description TEXT DEFAULT NULL,
            verification_token VARCHAR(255),
            reset_token VARCHAR(255),
            reset_token_expires DATETIME,
            avatar_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_admin BOOLEAN DEFAULT FALSE,
            UNIQUE KEY email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Tabela 'users' została utworzona.\n";

    // Tworzenie tabeli notifications
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Tabela 'notifications' została utworzona.\n";

    // Tworzenie tabeli petitions jeśli nie istnieje
    $pdo->exec("CREATE TABLE IF NOT EXISTS petitions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        addressee TEXT NOT NULL,
        category VARCHAR(100),
        target_signatures INT NOT NULL,
        created_by INT NOT NULL,
        status ENUM('active', 'closed', 'deleted') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_category (category)
    ) ENGINE=InnoDB");
    echo "Tabela 'petitions' została utworzona lub zaktualizowana.\n";

} catch(PDOException $e) {
    die("Błąd: " . $e->getMessage() . "\n");
}
