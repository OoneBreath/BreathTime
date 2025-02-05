<?php
require_once __DIR__ . '/../generate_sitemap.php';

// Ustaw ścieżkę do pliku blokady
$lockFile = __DIR__ . '/sitemap.lock';

// Sprawdź czy skrypt już nie jest uruchomiony
if (file_exists($lockFile)) {
    $lockTime = filemtime($lockFile);
    // Jeśli blokada jest starsza niż 1 godzina, usuń ją
    if (time() - $lockTime > 3600) {
        unlink($lockFile);
    } else {
        exit("Skrypt jest już uruchomiony\n");
    }
}

// Utwórz plik blokady
touch($lockFile);

try {
    // Generuj sitemap
    $sitemap = generateSitemap($pdo);
    file_put_contents(__DIR__ . '/../sitemap.xml', $sitemap);
    echo "Mapa strony została wygenerowana pomyślnie\n";
} catch (Exception $e) {
    error_log("Błąd generowania sitemap: " . $e->getMessage());
    echo "Wystąpił błąd podczas generowania mapy strony\n";
} finally {
    // Usuń plik blokady
    unlink($lockFile);
}
