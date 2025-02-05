<?php
// Wczytanie konfiguracji
$config = require_once 'database.php';

try {
    // Połączenie z MySQL (bez wybierania bazy danych)
    $pdo = new PDO(
        "mysql:host={$config['host']};charset={$config['charset']}", 
        $config['username'], 
        $config['password']
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

} catch(PDOException $e) {
    die("Błąd: " . $e->getMessage() . "\n");
}
