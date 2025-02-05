<?php
require 'vendor/autoload.php';
header('Content-Type: application/json');

// Klucz tajny Stripe
\Stripe\Stripe::setApiKey('sk_test_your_secret_key');

try {
    // Pobierz dane z żądania
    $json_str = file_get_contents('php://input');
    $json_obj = json_decode($json_str);

    $amount = $json_obj->amount;
    $payment_method_id = $json_obj->payment_method_id;

    // Utwórz intencję płatności
    $intent = \Stripe\PaymentIntent::create([
        'amount' => $amount * 100, // Stripe używa najmniejszej jednostki waluty (grosze)
        'currency' => 'pln',
        'payment_method' => $payment_method_id,
        'confirmation_method' => 'manual',
        'confirm' => true,
        'return_url' => 'https://breathtime.info/donate-success.html'
    ]);

    // Wyślij odpowiedź
    if ($intent->status == 'requires_action' &&
        $intent->next_action->type == 'use_stripe_sdk') {
        echo json_encode([
            'requires_action' => true,
            'payment_intent_client_secret' => $intent->client_secret
        ]);
    } else if ($intent->status == 'succeeded') {
        // Zapisz informacje o płatności do bazy danych
        $amount = $intent->amount / 100;
        $payment_id = $intent->id;
        $email = $intent->charges->data[0]->billing_details->email;
        
        // TODO: Dodaj zapis do bazy danych
        
        echo json_encode([
            'success' => true
        ]);
    } else {
        echo json_encode([
            'error' => 'Invalid PaymentIntent status'
        ]);
    }
} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
