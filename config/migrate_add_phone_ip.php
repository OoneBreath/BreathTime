<?php
require_once '../includes/config.php';

try {
    $sql = "ALTER TABLE users 
            ADD COLUMN phone VARCHAR(20) DEFAULT NULL,
            ADD COLUMN country_code VARCHAR(2) DEFAULT NULL,
            ADD COLUMN registration_ip VARCHAR(45) DEFAULT NULL,
            ADD COLUMN last_login_ip VARCHAR(45) DEFAULT NULL";
    
    $conn->exec($sql);
    echo "Pomyślnie dodano kolumny phone, country_code i IP do tabeli users\n";
} catch(PDOException $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}
