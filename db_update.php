<?php
require_once 'includes/config.php';

try {
    $db = Database::getInstance();
    
    // Sprawdź czy kolumny już istnieją
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($result->rowCount() == 0) {
        $db->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
    }
    
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'country_code'");
    if ($result->rowCount() == 0) {
        $db->query("ALTER TABLE users ADD COLUMN country_code VARCHAR(2) DEFAULT NULL");
    }
    
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'ip_address'");
    if ($result->rowCount() == 0) {
        $db->query("ALTER TABLE users ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL");
    }
    
    echo "Struktura bazy danych została zaktualizowana pomyślnie.";
} catch (Exception $e) {
    echo "Wystąpił błąd podczas aktualizacji bazy danych: " . $e->getMessage();
}
