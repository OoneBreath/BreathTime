<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/Mailer.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Brak tokenu weryfikacyjnego.';
} else {
    try {
        $db = Database::getInstance();
        
        // Sprawdź czy token istnieje i czy konto nie jest już zweryfikowane
        $user = $db->query(
            "SELECT id, name, email, status FROM users WHERE verification_token = ?",
            [$token]
        )->fetch();

        if (!$user) {
            $error = 'Nieprawidłowy token weryfikacyjny.';
        } elseif ($user['status'] === 'active') {
            $error = 'To konto jest już zweryfikowane.';
        } else {
            // Aktywuj konto
            $result = $db->query(
                "UPDATE users SET status = 'active', verification_token = NULL WHERE id = ?",
                [$user['id']]
            );

            if ($result) {
                // Wyślij email powitalny
                try {
                    $mailer = new Mailer();
                    $mailer->sendWelcomeEmail($user['email'], $user['name']);
                } catch (Exception $e) {
                    error_log("Błąd wysyłania maila powitalnego: " . $e->getMessage());
                }
                
                $success = 'Twoje konto zostało pomyślnie zweryfikowane! Możesz się teraz zalogować.';
            } else {
                throw new Exception('Nie udało się zaktualizować statusu konta.');
            }
        }
    } catch (Exception $e) {
        error_log("Błąd weryfikacji: " . $e->getMessage());
        $error = 'Wystąpił błąd podczas weryfikacji konta. Spróbuj ponownie później.';
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weryfikacja konta - BreathTime</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="content-box verification-page">
        <h1>Weryfikacja konta</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <div class="mt-4">
                    <a href="login.php" class="btn btn-primary">Przejdź do logowania</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .verification-page {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .btn-primary {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #45a049;
        }

        .mt-4 {
            margin-top: 20px;
        }
    </style>
</body>
</html>
