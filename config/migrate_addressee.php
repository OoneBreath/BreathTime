<?php
require_once '../includes/config.php';

try {
    $db = Database::getInstance();
    
    // Sprawdź czy kolumna już istnieje
    $result = $db->query("SHOW COLUMNS FROM petitions LIKE 'addressee'");
    if ($result->rowCount() == 0) {
        // Dodaj kolumnę addressee
        $db->query("ALTER TABLE petitions ADD COLUMN addressee TEXT NOT NULL DEFAULT 'Nie określono'");
        echo "Kolumna addressee została dodana do tabeli petitions.\n";
    } else {
        echo "Kolumna addressee już istnieje w tabeli petitions.\n";
    }
    
    echo "Migracja zakończona pomyślnie.\n";
    
} catch(Exception $e) {
    die("Błąd podczas migracji: " . $e->getMessage() . "\n");
}
