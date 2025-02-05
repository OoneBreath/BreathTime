<?php
// Ścieżka do katalogu e-booków
$ebooks_dir = __DIR__ . '/../ebooks';

// Tworzenie katalogu jeśli nie istnieje
if (!file_exists($ebooks_dir)) {
    mkdir($ebooks_dir, 0755, true);
    echo "Utworzono katalog ebooks/\n";
}

// Tworzenie przykładowych plików (tylko dla celów testowych)
$formats = ['pdf', 'epub', 'mobi'];
foreach ($formats as $format) {
    $file = $ebooks_dir . '/breathtime.' . $format;
    if (!file_exists($file)) {
        file_put_contents($file, "To jest przykładowy plik e-booka w formacie {$format}.\n");
        echo "Utworzono przykładowy plik {$format}\n";
    }
}

// Ustawienie odpowiednich uprawnień
chmod($ebooks_dir, 0755);
foreach ($formats as $format) {
    chmod($ebooks_dir . '/breathtime.' . $format, 0644);
}

echo "Inicjalizacja katalogu e-booków zakończona.\n";
