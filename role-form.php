<?php
require_once 'includes/config.php';
session_start();

$role = $_GET['role'] ?? '';
$type = $_GET['type'] ?? '';

$validRoles = [
    'volunteer' => 'Wolontariusz',
    'educator' => 'Edukator',
    'monitor' => 'Monitor',
    'foundation' => 'Fundacja',
    'tech_partner' => 'Partner Technologiczny',
    'edu_institution' => 'Instytucja Edukacyjna',
    'expert' => 'Ekspert',
    'strategic_partner' => 'Partner Strategiczny',
    'ambassador' => 'Ambasador'
];

$roleName = $validRoles[$role] ?? '';

if (!$roleName) {
    header('Location: index.php');
    exit();
}

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
    <title>Dołącz jako <?php echo htmlspecialchars($roleName); ?> - BreathTime</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/menu.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="assets/js/counter.js"></script>
    <script src="assets/js/stars.js"></script>
    <script src="assets/js/sphere.js"></script>
    <style>
        body {
            margin: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }

        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .role-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(3px);
            transition: all 1.5s ease-in-out;
        }

        .role-overlay.fade-out {
            opacity: 0;
            backdrop-filter: blur(0);
        }

        .role-overlay.success-out {
            background: rgba(0, 0, 0, 0);
            transition: all 1s ease-in-out;
        }

        .role-container {
            max-width: 600px;
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
            transform-origin: center center;
            transition: transform 1.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 1.5s ease-in-out;
        }

        .role-overlay.fade-out .role-container {
            animation: burn 1.2s ease-in-out forwards;
            transform: scale(0.9) rotate(5deg) translateY(-200vh);
            opacity: 0;
        }

        .role-overlay.success-out .role-container {
            animation: successBurst 1s ease-in-out forwards;
        }

        .role-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 15px;
            opacity: 0;
            background: radial-gradient(circle at center, 
                rgba(255, 100, 0, 0.5),
                transparent 70%);
            transition: opacity 1.2s ease-in-out;
            pointer-events: none;
        }

        .role-overlay.fade-out .role-container::before {
            opacity: 1;
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
            color: white;
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
            border: none;
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

        @keyframes burn {
            0% {
                box-shadow: 0 0 30px rgba(0, 168, 255, 0.3);
                border-color: rgba(0, 168, 255, 0.2);
                transform: scale(1) rotate(0deg);
            }
            20% {
                box-shadow: 0 0 50px rgba(255, 150, 0, 0.5);
                border-color: rgba(255, 150, 0, 0.5);
                transform: scale(1.02) rotate(1deg);
            }
            40% {
                box-shadow: 0 0 70px rgba(255, 100, 0, 0.6);
                border-color: rgba(255, 100, 0, 0.6);
                background: linear-gradient(to bottom, 
                    rgba(30, 60, 114, 0.95),
                    rgba(255, 100, 0, 0.2));
                transform: scale(1.01) rotate(-1deg);
            }
            60% {
                box-shadow: 0 -10px 100px rgba(255, 50, 0, 0.8);
                border-color: rgba(255, 50, 0, 0.8);
                background: linear-gradient(to bottom, 
                    rgba(30, 60, 114, 0.8),
                    rgba(255, 50, 0, 0.3));
                transform: scale(1.03) rotate(1deg);
            }
            80% {
                box-shadow: 0 -15px 120px rgba(255, 20, 0, 0.9);
                border-color: rgba(255, 20, 0, 0.9);
                background: linear-gradient(to bottom, 
                    rgba(255, 100, 0, 0.6),
                    rgba(255, 20, 0, 0.4));
                transform: scale(1.02) rotate(-1deg);
            }
            100% {
                box-shadow: 0 -20px 150px rgba(255, 0, 0, 1);
                border-color: rgba(255, 0, 0, 1);
                background: linear-gradient(to bottom, 
                    rgba(255, 50, 0, 0.8),
                    rgba(255, 0, 0, 0.5));
                transform: scale(1) rotate(0deg);
            }
        }

        @keyframes successBurst {
            0% {
                transform: scale(1);
                box-shadow: 0 0 30px rgba(0, 168, 255, 0.3);
                background: rgba(30, 60, 114, 0.95);
            }
            40% {
                transform: scale(1.1);
                box-shadow: 0 0 100px rgba(46, 213, 115, 0.8);
                background: rgba(46, 213, 115, 0.9);
            }
            100% {
                transform: scale(0) rotate(10deg);
                box-shadow: 0 0 200px rgba(46, 213, 115, 1);
                opacity: 0;
                background: rgba(46, 213, 115, 1);
            }
        }
    </style>
</head>
<body>
    <!-- Menu -->
    <?php include 'includes/menu.php'; ?>

    <!-- Canvas dla gwiazd i kuli -->
    <div id="canvas-container"></div>

    <!-- Pasek postępu -->
    <div class="progress-bar">
        <div class="progress-fill"></div>
    </div>

    <div class="role-overlay">
        <div class="role-container">
            <h1 class="role-title">Dołącz jako <?php echo htmlspecialchars($roleName); ?></h1>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="login-message">
                    <p>Aby dołączyć jako <?php echo htmlspecialchars($roleName); ?>, musisz się najpierw zalogować.</p>
                    <div class="login-buttons">
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="role-btn role-btn-primary">Zaloguj się</a>
                        <span>lub</span>
                        <a href="register.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="role-btn role-btn-secondary">Zarejestruj się</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="role-description">
                    <p>Dołączając jako <?php echo htmlspecialchars($roleName); ?>, staniesz się częścią społeczności BreathTime i będziesz mógł aktywnie uczestniczyć w naszej misji na rzecz czystego powietrza.</p>
                </div>
                
                <form id="roleForm">
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                    
                    <div class="form-group">
                        <label class="role-label">Dlaczego chcesz dołączyć jako <?php echo htmlspecialchars($roleName); ?>?</label>
                        <textarea name="description" class="role-textarea" required 
                                placeholder="Opisz swoje doświadczenie, motywację i jak chciałbyś przyczynić się do naszej misji..."></textarea>
                    </div>
                    
                    <div class="role-buttons">
                        <button type="button" class="role-btn role-btn-secondary" onclick="closeRoleForm()">Anuluj</button>
                        <button type="submit" class="role-btn role-btn-primary">Wyślij zgłoszenie</button>
                    </div>
                </form>

                <script>
                    document.getElementById('roleForm').addEventListener('submit', function(event) {
                        event.preventDefault();
                        const form = event.target;
                        const formData = new FormData(form);

                        // Wyłącz przycisk submit
                        const submitButton = form.querySelector('button[type="submit"]');
                        if (submitButton) submitButton.disabled = true;

                        fetch('register-role.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // Animacja sukcesu
                                const container = document.querySelector('.role-container');
                                container.style.animation = 'successBurst 1s ease-in-out forwards';
                                
                                setTimeout(() => {
                                    // Pokaż stronę podziękowania
                                    window.location.href = 'thank-you.php?type=role&role=' + 
                                        encodeURIComponent(formData.get('role')) +
                                        '&message=' + encodeURIComponent(data.message);
                                }, 1000);
                            } else {
                                // Włącz z powrotem przycisk submit
                                if (submitButton) submitButton.disabled = false;
                                alert(data.message || 'Wystąpił błąd podczas wysyłania formularza');
                            }
                        })
                        .catch(error => {
                            // Włącz z powrotem przycisk submit
                            if (submitButton) submitButton.disabled = false;
                            alert('Wystąpił błąd podczas wysyłania formularza');
                        });
                    });

                    function closeRoleForm() {
                        const overlay = document.querySelector('.role-overlay');
                        overlay.classList.add('fade-out');
                        
                        setTimeout(() => {
                            window.close();
                        }, 1500);
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Inicjalizacja gwiazd i kuli
        document.addEventListener('DOMContentLoaded', function() {
            initStars();
            initSphere();
        });
    </script>
</body>
</html>
