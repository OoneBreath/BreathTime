<?php
session_start();
require_once 'includes/config.php';

// Pobierz statystyki petycji
$db = Database::getInstance();
$stats = $db->query(
    "SELECT 
        (SELECT COUNT(*) FROM petitions WHERE status = 'active') as active_petitions,
        (SELECT SUM(current_signatures) FROM petitions) as total_signatures"
)->fetch();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BreathTime</title>
    <link rel="icon" type="image/svg+xml" href="/images/breathtime-logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="/images/breathtime-logo.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="assets/js/counter.js"></script>
    <style>
        .message-overlay {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.9);
            padding: 20px;
            border-radius: 10px;
            z-index: 1000;
            color: white;
            text-align: center;
            max-width: 80%;
            box-shadow: 0 0 20px rgba(0, 168, 255, 0.3);
            border: 1px solid rgba(0, 168, 255, 0.2);
        }
        .message-overlay h3 {
            color: #4a90e2;
            margin-top: 0;
        }
        .message-overlay button {
            background: #4a90e2;
            border: none;
            padding: 10px 20px;
            color: white;
            border-radius: 5px;
            margin-top: 15px;
            cursor: pointer;
        }
        .message-overlay button:hover {
            background: #357abd;
        }
        .role-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .role-container {
            background: rgba(30, 60, 114, 0.95);
            border-radius: 15px;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 0 30px rgba(0, 168, 255, 0.3);
            border: 1px solid rgba(0, 168, 255, 0.2);
            color: white;
            position: relative;
            backdrop-filter: blur(10px);
        }

        .role-title {
            color: #4a90e2;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }

        .role-description {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(0, 168, 255, 0.2);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .role-textarea {
            width: 100%;
            min-height: 150px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(0, 168, 255, 0.2);
            border-radius: 8px;
            color: white;
            font-size: 16px;
            resize: vertical;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .role-textarea:focus {
            outline: none;
            border-color: rgba(0, 168, 255, 0.5);
            box-shadow: 0 0 10px rgba(0, 168, 255, 0.2);
        }

        .role-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .role-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .role-btn-primary {
            background: #4a90e2;
            color: white;
        }

        .role-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .role-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .role-btn-primary:hover {
            background: #357abd;
        }

        .role-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .role-label {
            display: block;
            margin-bottom: 10px;
            font-size: 18px;
            color: #4a90e2;
        }

        .login-message {
            text-align: center;
            padding: 20px;
            background: rgba(74, 144, 226, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .login-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['show_role_message']) && $_SESSION['show_role_message']): ?>
    <div class="message-overlay" id="roleMessage">
        <h3>Dziękujemy za zgłoszenie!</h3>
        <p><?php echo $_SESSION['success']; ?></p>
        <button onclick="document.getElementById('roleMessage').style.display='none'">Zamknij</button>
    </div>
    <?php 
        unset($_SESSION['show_role_message']);
        unset($_SESSION['success']);
    endif; ?>
    <!-- Logo -->
    <a href="./index.php" class="logo">
        <img src="./images/breathtime-logo.svg" alt="BreathTime Logo">
    </a>

    <!-- Kontener globusa -->
    <div id="earth-container">
        <iframe id="content-frame" name="content-frame"></iframe>
    </div>

    <!-- Główny tekst (motto) -->
    <div class="main-text">
        <p>Jeden oddech dla lepszego jutra</p>
    </div>

    <!-- Lewe zakładki -->
    <nav class="left-tabs">
        <a href="petitions.php" class="left-tab">
            <span class="tab-content">
                <span class="emoji">📝</span>
                <span class="tab-text">Petycje</span>
                <?php if ($stats['active_petitions'] > 0): ?>
                    <span class="counter"><?php echo $stats['active_petitions']; ?></span>
                <?php endif; ?>
            </span>
        </a>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="login.php" class="left-tab">
                <span class="tab-content">
                    <span class="emoji">🔐</span>
                    <span class="tab-text">Zaloguj się</span>
                </span>
            </a>
        <?php else: ?>
            <a href="my-petitions.php" class="left-tab">
                <span class="tab-content">
                    <span class="emoji">📋</span>
                    <span class="tab-text">Moje petycje</span>
                </span>
            </a>
            <a href="dashboard.php" class="left-tab">
                <span class="tab-content">
                    <span class="emoji">👤</span>
                    <span class="tab-text">Panel</span>
                </span>
            </a>
            <a href="settings.php" class="left-tab">
                <span class="tab-content">
                    <span class="emoji">⚙️</span>
                    <span class="tab-text">Ustawienia</span>
                </span>
            </a>
            <a href="notifications.php" class="left-tab">
                <span class="tab-content">
                    <span class="emoji">🔔</span>
                    <span class="tab-text">Powiadomienia</span>
                </span>
            </a>
            <a href="logout.php" class="left-tab">
                <span class="tab-content">
                    <span class="emoji">🚪</span>
                    <span class="tab-text">Wyloguj</span>
                </span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- Menu falowe -->
    <nav class="wave-menu">
        <a href="./breathtime.html" class="menu-item" target="content-frame">BreathTime</a>
        <a href="./benefits.html" class="menu-item" target="content-frame">Korzyści</a>
        <a href="./how-to-help.html" class="menu-item" target="content-frame">Jak Pomóc</a>
        <a href="./contact.html" class="menu-item" target="content-frame">Kontakt</a>
        <a href="./partners.html" class="menu-item" target="content-frame">Partners</a>
    </nav>

    <!-- Zakładki książkowe -->
    <nav class="book-tabs">
        <a href="./ebook.html" class="book-tab" target="content-frame">
            <span class="tab-content"><span class="emoji">📚</span>E-book</span>
        </a>
        <a href="./blog.html" class="book-tab" target="content-frame">
            <span class="tab-content"><span class="emoji">📝</span>Blog</span>
        </a>
        <a href="./support.html" class="book-tab" target="content-frame">
            <span class="tab-content"><span class="emoji">💝</span>Wesprzyj</span>
        </a>
        <a href="./faq.html" class="book-tab" target="content-frame">
            <span class="tab-content"><span class="emoji">❓</span>FAQ</span>
        </a>
        <div class="book-divider"></div>
        <a href="./anti-scam.html" class="book-tab" target="content-frame">
            <span class="tab-content"><span class="emoji">🛡️</span>Anti-Scam</span>
        </a>
        <a href="./terms.html" class="book-tab" target="content-frame">
            <span class="tab-content"><span class="emoji">📋</span>Terms</span>
        </a>
        <a href="./privacy.html" class="book-tab" target="content-frame">
            <span class="tab-content"><span class="emoji">🔒</span>Privacy</span>
        </a>
    </nav>

    <!-- Pasek informacyjny -->
    <div class="news-ticker">
        <div class="news-ticker-content">
            Dołącz do nas w walce o czyste powietrze! <a href="register.php" style="color: rgba(255, 200, 0, 0.8); text-decoration: none;">Zarejestruj się teraz</a> i zostań częścią naszej społeczności. Każdy oddech się liczy!
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/navigation.js"></script>
    <script>
        // Funkcja do ładowania formularza
        function loadRoleForm(role, type) {
            // Otwórz formularz w nowym oknie
            window.open(`role-form.php?role=${role}&type=${type}`, '_blank');
        }

        // Funkcja zamykająca formularz
        function closeRoleForm() {
            const overlay = document.querySelector('.role-overlay');
            if (overlay) {
                overlay.remove();
            }
        }

        // Funkcja wysyłająca formularz
        function submitRoleForm(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            fetch('register-role.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Twoja prośba o rolę została przyjęta. Powiadomimy Cię o decyzji mailowo.');
                    closeRoleForm();
                } else {
                    alert(data.message || 'Wystąpił błąd podczas wysyłania formularza');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas wysyłania formularza');
            });
        }

        // Zamykanie formularza po kliknięciu poza nim
        document.addEventListener('click', function(event) {
            const overlay = document.querySelector('.role-overlay');
            const container = document.querySelector('.role-container');
            if (overlay && event.target === overlay && !container.contains(event.target)) {
                closeRoleForm();
            }
        });

        // Sprawdź czy powinniśmy otworzyć formularz roli
        const urlParams = new URLSearchParams(window.location.search);
        const role = urlParams.get('role');
        const type = urlParams.get('type');
        if (role) {
            loadRoleForm(role, type);
        }
    </script>
</body>
</html>
