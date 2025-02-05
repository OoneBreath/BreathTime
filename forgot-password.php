<?php
require_once 'includes/config.php';
require_once 'includes/Mailer.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    $db = Database::getInstance();
    $user = $db->query("SELECT id, name FROM users WHERE email = ?", [$email])->fetch();
    
    if ($user) {
        // Generowanie tokenu resetowania hasła
        $reset_token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        try {
            $db->query(
                "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?",
                [$reset_token, $expiry, $user['id']]
            );
            
            // Wysyłanie maila z linkiem do resetowania hasła
            $mailer = new Mailer();
            $mailer->sendPasswordResetEmail($email, $user['name'], $reset_token);
            
            $success = 'Link do resetowania hasła został wysłany na podany adres email.';
            
        } catch (Exception $e) {
            $error = 'Wystąpił błąd podczas wysyłania linku resetującego.';
            error_log($e->getMessage());
        }
    } else {
        // Dla bezpieczeństwa pokazujemy tę samą wiadomość
        $success = 'Link do resetowania hasła został wysłany na podany adres email.';
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetowanie hasła - BreathTime</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .reset-container {
            max-width: 400px;
            margin: 120px auto 40px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 200, 0, 0.1);
        }

        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .reset-header h1 {
            color: white;
            font-size: 2em;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 20px rgba(255, 200, 0, 0.5);
        }

        .reset-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1em;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: white;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255, 200, 0, 0.1);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.2);
            color: white;
            font-size: 1em;
        }

        .form-group input:focus {
            outline: none;
            border-color: rgba(255, 200, 0, 0.3);
            box-shadow: 0 0 10px rgba(255, 200, 0, 0.1);
        }

        .reset-button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, rgba(255, 200, 0, 0.8), rgba(255, 180, 0, 0.8));
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(255, 200, 0, 0.3);
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.3);
            color: #ff4444;
        }

        .success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid rgba(0, 255, 0, 0.3);
            color: #4CAF50;
        }

        .login-link {
            text-align: center;
            margin-top: 1rem;
        }

        .login-link a {
            color: rgba(255, 200, 0, 0.8);
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>Resetowanie hasła</h1>
            <p>Podaj swój adres email, a wyślemy Ci link do zresetowania hasła.</p>
        </div>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <button type="submit" class="reset-button">Wyślij link resetujący</button>
        </form>

        <div class="login-link">
            <a href="login.php">Powrót do logowania</a>
        </div>
    </div>
</body>
</html>
