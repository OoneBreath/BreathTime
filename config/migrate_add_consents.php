<?php
require_once '../includes/config.php';

try {
    $sql = "ALTER TABLE users 
            ADD COLUMN terms_accepted TIMESTAMP DEFAULT NULL,
            ADD COLUMN marketing_consent BOOLEAN DEFAULT FALSE";
    
    $conn->exec($sql);
    echo "Pomyślnie dodano kolumny terms_accepted i marketing_consent do tabeli users\n";
} catch(PDOException $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}
