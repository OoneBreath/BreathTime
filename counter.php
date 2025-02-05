<?php
header('Content-Type: application/json');

$counterFile = 'counter.txt';

// Jeśli plik nie istnieje, utwórz go z wartością 0
if (!file_exists($counterFile)) {
    file_put_contents($counterFile, '0');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Zwiększ licznik
    $count = (int)file_get_contents($counterFile);
    $count++;
    file_put_contents($counterFile, (string)$count);
} else {
    // Pobierz aktualną wartość
    $count = (int)file_get_contents($counterFile);
}

echo json_encode(['count' => $count]);
?>
