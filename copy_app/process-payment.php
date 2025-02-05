<?php
require_once('vendor/autoload.php');
require_once('includes/Database.php');

// Konfiguracja Stripe
\Stripe\Stripe::setApiKey('your_secret_key'); // Tutaj trzeba będzie wstawić prawdziwy klucz sekretny

try {
    // Odbieranie danych z żądania
    $json_str = file_get_contents('php://input');
    $json_obj = json_decode($json_str);

    // Inicjalizacja bazy danych
    $db = Database::getInstance();

    // Tworzenie klienta w Stripe
    $customer = \Stripe\Customer::create([
        'email' => $json_obj->email,
        'source'  => $json_obj->token,
    ]);

    // Tworzenie opłaty
    $charge = \Stripe\Charge::create([
        'customer' => $customer->id,
        'amount'   => $json_obj->amount,
        'currency' => 'pln',
        'description' => 'E-book BreathTime',
    ]);

    // Zapisywanie informacji o zakupie w bazie danych
    $download_token = $db->createPurchase(
        $json_obj->email,
        $charge->id,
        $json_obj->amount
    );
    
    // Logowanie sukcesu
    $db->log('payment_success', 'Płatność zakończona sukcesem', [
        'email' => $json_obj->email,
        'charge_id' => $charge->id,
        'amount' => $json_obj->amount
    ]);

    // Wysyłanie e-maila z linkiem do pobrania
    $to = $json_obj->email;
    $subject = "Twój e-book BreathTime";
    $message = "Dziękujemy za zakup e-booka BreathTime!\n\n";
    $message .= "Możesz pobrać swoją kopię w następujących formatach:\n\n";
    $message .= "PDF: https://breathtime.info/download.php?token={$download_token}&format=pdf\n";
    $message .= "EPUB: https://breathtime.info/download.php?token={$download_token}&format=epub\n";
    $message .= "MOBI: https://breathtime.info/download.php?token={$download_token}&format=mobi\n\n";
    $message .= "Link jest ważny przez 30 dni.\n\n";
    $message .= "Dziękujemy za wsparcie inicjatywy BreathTime!\n";
    
    $headers = [
        'From' => 'BreathTime <no-reply@breathtime.info>',
        'Content-Type' => 'text/plain; charset=UTF-8'
    ];
    
    mail($to, $subject, $message, $headers);

    // Zwracanie sukcesu
    echo json_encode(['success' => true]);

} catch(\Stripe\Exception\CardException $e) {
    // Logowanie błędu karty
    $db->log('payment_error', 'Błąd karty płatniczej', [
        'error' => $e->getMessage(),
        'email' => $json_obj->email ?? null
    ]);
    
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Exception $e) {
    // Logowanie ogólnego błędu
    $db->log('payment_error', 'Ogólny błąd płatności', [
        'error' => $e->getMessage(),
        'email' => $json_obj->email ?? null
    ]);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Wystąpił błąd podczas przetwarzania płatności.']);
}
