<?php
require_once '../includes/config.php';

try {
    $db = Database::getInstance();
    
    // Sprawdź czy kolumna już istnieje
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    if ($result->rowCount() == 0) {
        // Dodaj kolumnę avatar
        $db->query("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
        echo "Kolumna avatar została dodana do tabeli users.\n";
        
        // Utwórz katalog na avatary jeśli nie istnieje
        $avatar_dir = __DIR__ . '/../uploads/avatars';
        if (!file_exists($avatar_dir)) {
            mkdir($avatar_dir, 0755, true);
            echo "Utworzono katalog na avatary.\n";
        }
    } else {
        echo "Kolumna avatar już istnieje w tabeli users.\n";
    }
    
    echo "Migracja zakończona pomyślnie.\n";
    
} catch(Exception $e) {
    die("Błąd podczas migracji: " . $e->getMessage() . "\n");
}
