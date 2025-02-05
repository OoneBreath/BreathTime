<?php
session_start();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

// Wyczyść komunikaty po wyświetleniu
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dziękujemy - BreathTime</title>
    <style>
        body { 
            font-family: Arial, sans-serif;
            background: #1e3c72;
            color: white;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(0,0,0,0.3);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        h1 {
            margin: 0 0 20px 0;
            font-size: 24px;
        }
        p {
            margin: 0 0 20px 0;
            line-height: 1.6;
            opacity: 0.9;
        }
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid rgba(0, 255, 0, 0.2);
        }
        .error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.2);
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="icon">✨</div>
            <div class="message success">
                <?php echo nl2br(htmlspecialchars($success)); ?>
            </div>
            <p>
                Możesz teraz zamknąć to okno i wrócić do przeglądania naszej strony.
                Powiadomimy Cię o statusie Twojego zgłoszenia poprzez e-mail.
            </p>
        <?php elseif ($error): ?>
            <div class="icon">❌</div>
            <div class="message error">
                <?php echo nl2br(htmlspecialchars($error)); ?>
            </div>
            <p>
                Możesz spróbować ponownie lub skontaktować się z nami, jeśli problem będzie się powtarzał.
            </p>
        <?php else: ?>
            <div class="icon">🤔</div>
            <h1>Coś poszło nie tak</h1>
            <p>
                Przepraszamy, ale nie mogliśmy przetworzyć Twojego zgłoszenia.
                Spróbuj ponownie lub skontaktuj się z nami.
            </p>
        <?php endif; ?>
        
        <a href="javascript:window.close();" class="btn">Zamknij okno</a>
    </div>
</body>
</html>
