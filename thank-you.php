<?php
$type = isset($_GET['type']) ? $_GET['type'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';

$roleNames = [
    'volunteer' => 'Wolontariusza',
    'educator' => 'Edukatora',
    'ambassador' => 'Ambasadora',
    'monitor' => 'Monitora',
];

$roleName = isset($roleNames[$role]) ? $roleNames[$role] : $role;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dziękujemy - BreathTime</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .thank-you-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 20px;
        }

        .thank-you-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            color: white;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 0 30px rgba(0, 168, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .thank-you-icon {
            font-size: 64px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        .thank-you-title {
            font-size: 32px;
            margin-bottom: 20px;
            color: #4a90e2;
        }

        .thank-you-text {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .thank-you-button {
            display: inline-block;
            padding: 12px 30px;
            background: #4a90e2;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .thank-you-button:hover {
            background: #357abd;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="thank-you-container">
        <div class="thank-you-card">
            <?php if ($type === 'role'): ?>
                <div class="thank-you-icon">🌟</div>
                <h1 class="thank-you-title">Dziękujemy za dołączenie!</h1>
                <p class="thank-you-text">
                    <?php 
                    if (isset($_GET['message'])) {
                        echo htmlspecialchars($_GET['message']);
                    } else {
                        echo "Wspaniale, że zdecydowałeś się dołączyć do nas jako " . htmlspecialchars($roleName) . "! " .
                             "Twoje zgłoszenie zostało przyjęte. Wkrótce skontaktujemy się z Tobą, aby omówić szczegóły współpracy.";
                    }
                    ?>
                </p>
                <button onclick="window.close();" class="thank-you-button">Zamknij okno</button>
            <?php else: ?>
                <div class="thank-you-icon">👍</div>
                <h1 class="thank-you-title">Dziękujemy!</h1>
                <p class="thank-you-text">
                    Twoje zgłoszenie zostało przyjęte. Dziękujemy za zainteresowanie.
                </p>
                <button onclick="window.close();" class="thank-you-button">Zamknij okno</button>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Dostosuj rozmiar okna
        window.onload = function() {
            if (window.opener) {
                // Dostosuj rozmiar do zawartości
                const content = document.querySelector('.thank-you-card');
                if (content) {
                    const padding = 40; // Dodatkowy padding dla komfortu
                    const width = Math.min(content.offsetWidth + padding, window.screen.availWidth * 0.9);
                    const height = Math.min(content.offsetHeight + padding, window.screen.availHeight * 0.9);
                    window.resizeTo(width, height);
                    window.moveTo((screen.width - width) / 2, (screen.height - height) / 2);
                }
            }
        };
    </script>
</body>
</html>
