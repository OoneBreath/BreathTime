<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'onebreath_baza');
define('DB_USER', 'onebreath_info');
define('DB_PASS', 'mZkdNfUaseEn');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Sprawdź czy kolumny już istnieją
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('role', $columns)) {
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN role ENUM(
                'volunteer',
                'educator',
                'monitor',
                'foundation',
                'tech_partner',
                'edu_institution',
                'expert',
                'strategic_partner',
                'ambassador'
            ) DEFAULT NULL
        ");
        echo "Kolumna role została dodana.\n";
    }
    
    if (!in_array('role_verified', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role_verified BOOLEAN DEFAULT FALSE");
        echo "Kolumna role_verified została dodana.\n";
    }
    
    if (!in_array('role_application_date', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role_application_date DATETIME DEFAULT NULL");
        echo "Kolumna role_application_date została dodana.\n";
    }
    
    if (!in_array('role_verification_date', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role_verification_date DATETIME DEFAULT NULL");
        echo "Kolumna role_verification_date została dodana.\n";
    }
    
    echo "Migracja zakończona pomyślnie.\n";
    
} catch(PDOException $e) {
    die("Błąd: " . $e->getMessage() . "\n");
}
?>
