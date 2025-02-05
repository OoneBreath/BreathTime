<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ustawienie kodowania
    mb_internal_encoding('UTF-8');
    
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    // Konwersja na UTF-8 jeśli potrzebne
    $name = mb_convert_encoding($name, 'UTF-8', 'auto');
    $subject = mb_convert_encoding($subject, 'UTF-8', 'auto');
    $message = mb_convert_encoding($message, 'UTF-8', 'auto');

    // Walidacja
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Wszystkie pola są wymagane'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Nieprawidłowy adres email'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Konfiguracja emaila
    $to = "info@breathtime.info";
    $email_subject = "=?UTF-8?B?" . base64_encode("Nowa wiadomość ze strony: " . $subject) . "?=";
    $email_body = "Otrzymałeś nową wiadomość.\n\n".
                 "Szczegóły:\n\n".
                 "Imię: $name\n".
                 "Email: $email\n".
                 "Temat: $subject\n".
                 "Wiadomość:\n$message\n";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($name) . "?= <$email>\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Wysyłanie emaila
    if (mail($to, $email_subject, $email_body, $headers)) {
        echo json_encode(['status' => 'success', 'message' => 'Wiadomość została wysłana'], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Wystąpił błąd podczas wysyłania wiadomości'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metoda nie jest dozwolona'], JSON_UNESCAPED_UNICODE);
}
