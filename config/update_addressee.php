<?php
require_once '../includes/config.php';

try {
    $db = Database::getInstance();
    
    // Aktualizuj adresata dla petycji o ID 1
    $db->query(
        "UPDATE petitions SET addressee = ? WHERE id = ?",
        ["Rząd Rzeczypospolitej Polskiej", 1]
    );
    
    echo "Adresat został zaktualizowany pomyślnie.\n";
    
} catch(Exception $e) {
    die("Błąd podczas aktualizacji: " . $e->getMessage() . "\n");
}
