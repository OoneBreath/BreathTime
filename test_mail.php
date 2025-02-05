<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/Mailer.php';

try {
    $mailer = new Mailer();
    
    // Testowe połączenie z serwerem SMTP
    echo "<h2>Test połączenia SMTP:</h2>";
    $smtp = fsockopen('mail.breathtime.info', 465, $errno, $errstr, 10);
    if (!$smtp) {
        echo "<p style='color: red'>Nie można połączyć się z serwerem SMTP: $errstr ($errno)</p>";
    } else {
        echo "<p style='color: green'>Połączenie z serwerem SMTP udane!</p>";
        fclose($smtp);
    }
    
    // Testowe wysłanie maila
    echo "<h2>Test wysyłania maila:</h2>";
    echo "<pre>";
    $result = $mailer->sendVerificationEmail(
        'info@breathtime.info',
        'Test User',
        'test_token_123'
    );
    echo "</pre>";
    
    if ($result) {
        echo "<p style='color: green'>Mail został wysłany pomyślnie!</p>";
    } else {
        echo "<p style='color: red'>Wystąpił problem z wysłaniem maila.</p>";
    }
} catch (Exception $e) {
    echo "<h2 style='color: red'>Błąd:</h2>";
    echo "<pre>";
    echo "Wiadomość: " . $e->getMessage() . "\n";
    echo "Plik: " . $e->getFile() . "\n";
    echo "Linia: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}

// Sprawdźmy też konfigurację PHP mail
echo "<h2>Konfiguracja PHP mail:</h2>";
echo "<pre>";
var_dump([
    'SMTP' => ini_get('SMTP'),
    'smtp_port' => ini_get('smtp_port'),
    'sendmail_path' => ini_get('sendmail_path')
]);
echo "</pre>";
?>
