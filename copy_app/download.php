<?php
session_start();
require_once('includes/Database.php');

// Inicjalizacja bazy danych
$db = Database::getInstance();

// Funkcja weryfikująca token
function verify_download_token($token) {
    // TODO: Dodaj połączenie z bazą danych i weryfikację tokenu
    // Przykładowa implementacja:
    /*
    $db = new PDO("mysql:host=localhost;dbname=breathtime", "username", "password");
    $stmt = $db->prepare("SELECT * FROM purchases WHERE download_token = ? AND is_valid = 1");
    $stmt->execute([$token]);
    return $stmt->fetch();
    */
    
    // Tymczasowo zwracamy true dla testów
    return true;
}

// Funkcja logująca pobranie
function log_download($token, $format) {
    // TODO: Dodaj logowanie do bazy danych
    /*
    $db = new PDO("mysql:host=localhost;dbname=breathtime", "username", "password");
    $stmt = $db->prepare("INSERT INTO downloads (token, format, timestamp) VALUES (?, ?, NOW())");
    $stmt->execute([$token, $format]);
    */
}

// Sprawdzanie tokenu
$token = $_GET['token'] ?? '';
if (empty($token)) {
    $db->log('download_error', 'Próba pobrania bez tokenu');
    die('Nieprawidłowy token dostępu.');
}

// Weryfikacja tokenu
$purchase = $db->verifyToken($token);
if (!$purchase) {
    $db->log('download_error', 'Nieprawidłowy token', ['token' => $token]);
    die('Token jest nieprawidłowy lub wygasł.');
}

// Sprawdzanie formatu
$format = $_GET['format'] ?? 'pdf';
$allowed_formats = ['pdf', 'epub', 'mobi'];
if (!in_array($format, $allowed_formats)) {
    $format = 'pdf';
}

// Ścieżki do plików e-booka
$file_paths = [
    'pdf' => __DIR__ . '/ebooks/breathtime.pdf',
    'epub' => __DIR__ . '/ebooks/breathtime.epub',
    'mobi' => __DIR__ . '/ebooks/breathtime.mobi'
];

$file = $file_paths[$format];

// Sprawdzanie czy plik istnieje
if (!file_exists($file)) {
    $db->log('download_error', 'Plik nie istnieje', [
        'token' => $token,
        'format' => $format,
        'path' => $file
    ]);
    die('Przepraszamy, plik nie jest dostępny.');
}

// Logowanie pobrania
$db->logDownload($token, $format);

// Ustawienie nagłówków do pobrania
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="breathtime_ebook.' . $format . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Wysyłanie pliku
readfile($file);
exit;
