<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/Mailer.php';

try {
    $mailer = new Mailer();
    $result = $mailer->sendVerificationEmail('test@example.com', 'Test User', 'test_token');
    echo "Mail wysłany pomyślnie";
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage();
}
