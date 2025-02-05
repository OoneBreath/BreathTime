<?php
session_start();
require_once __DIR__ . '/config.php';

$page_title = $page_title ?? 'BreathTime';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="BreathTime - Twoje miejsce do składania i przeglądania petycji online. Dołącz do nas i wspieraj ważne sprawy społeczne.">
    <meta name="keywords" content="petycje online, petycje internetowe, podpisz petycję, składanie petycji, aktywizm społeczny">
    <meta name="author" content="BreathTime">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title ?? 'BreathTime'); ?>">
    <meta property="og:description" content="BreathTime - Twoje miejsce do składania i przeglądania petycji online. Dołącz do nas i wspieraj ważne sprawy społeczne.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="BreathTime">
    <title><?php echo $page_title ?? 'BreathTime'; ?></title>
    <link rel="icon" type="image/svg+xml" href="/images/breathtime-logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="/images/breathtime-logo.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --background-color: #111827;
            --secondary-bg: #1f2937;
            --content-bg: #374151;
            --text-color: #f3f4f6;
            --text-light: #9ca3af;
            --accent: #3b82f6;
            --accent-dark: #2563eb;
            --border: #4b5563;
            --glass-effect: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --success: #28a745;
            --success-light: rgba(40, 167, 69, 0.2);
            --success-border: rgba(40, 167, 69, 0.3);
            --warning: #ffc107;
            --warning-light: rgba(255, 193, 7, 0.2);
            --warning-border: rgba(255, 193, 7, 0.3);
            --danger: #dc3545;
            --danger-light: rgba(220, 53, 69, 0.2);
            --danger-border: rgba(220, 53, 69, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.5;
            min-height: 100vh;
        }

        .tech-header {
            background: var(--secondary-bg);
            border-bottom: 1px solid var(--border);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            padding: 0.25rem 0;
        }

        .header-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0.125rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            text-decoration: none;
            color: var(--text-color);
            filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.2));
            transition: all 0.3s ease;
        }

        .logo-container:hover {
            filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.3));
        }

        .logo-img {
            height: 6rem;
            width: auto;
            transition: transform 0.3s ease;
            filter: brightness(0) invert(1);
            margin: -0.75rem 0;
        }

        .logo-container:hover .logo-img {
            transform: scale(1.05);
        }

        .logo-text {
            font-size: 2.5rem;
            font-weight: 600;
            letter-spacing: -0.025em;
            color: var(--text-color);
        }

        .tech-nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            font-size: 1.2rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            background: var(--glass-effect);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
        }

        .nav-link:hover {
            color: var(--text-color);
            background: var(--content-bg);
            border-color: var(--accent);
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.25rem;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .nav-user:hover {
            color: var(--text-color);
            opacity: 0.9;
        }

        .nav-avatar {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            overflow: hidden;
            background: var(--content-bg);
        }

        .nav-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .nav-avatar .avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-avatar .avatar-placeholder i {
            font-size: 24px;
            color: var(--text-light);
        }

        .main-content {
            max-width: 1280px;
            margin: 7rem auto 2rem;
            padding: 0 2rem;
        }

        .content-box {
            background: var(--content-bg);
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            padding: 3rem;
            margin: 0 auto;
            border: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.75rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            background: var(--secondary-bg);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            margin-top: 1.25rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.625rem;
            overflow: hidden;
        }

        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: rgba(255, 255, 255, 0.1);
            font-weight: 600;
            color: #ffc800;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        td a {
            color: #ffc800;
            text-decoration: none;
        }

        td a:hover {
            text-decoration: underline;
        }

        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875em;
            display: inline-block;
        }

        .status-badge.active, .status-badge.published {
            background: var(--success-light);
            color: var(--success);
        }

        .status-badge.draft, .status-badge.pending {
            background: var(--warning-light);
            color: var(--warning);
        }

        .status-badge.blocked {
            background: var(--danger-light);
            color: var(--danger);
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.25rem;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-sm {
            padding: 0.4rem 0.75rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-dark);
        }

        .btn-success {
            background: var(--success-light);
            color: var(--success);
        }

        .btn-success:hover {
            background: rgba(40, 167, 69, 0.3);
        }

        .btn-warning {
            background: var(--warning-light);
            color: var(--warning);
        }

        .btn-warning:hover {
            background: rgba(255, 193, 7, 0.3);
        }

        .btn-danger {
            background: var(--danger-light);
            color: var(--danger);
        }

        .btn-danger:hover {
            background: rgba(220, 53, 69, 0.3);
        }

        .btn-block {
            width: 100%;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.25rem;
            text-align: center;
        }

        .alert-success {
            background: var(--success-light);
            border: 1px solid var(--success-border);
            color: var(--success);
        }

        .alert-danger {
            background: var(--danger-light);
            border: 1px solid var(--danger-border);
            color: var(--danger);
        }

        /* Utilities */
        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .align-items-center {
            align-items: center;
        }

        .d-inline {
            display: inline-block;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .text-center {
            text-align: center;
        }

        @media (max-width: 640px) {
            .header-content {
                padding: 0.25rem 1rem;
                flex-direction: column;
                gap: 0.75rem;
            }

            .logo-img {
                height: 4.5rem;
                margin: -0.5rem 0;
            }

            .logo-text {
                font-size: 2rem;
            }

            .tech-nav {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
                gap: 1rem;
            }

            .nav-link {
                font-size: 1.1rem;
                padding: 0.5rem 1rem;
            }

            .main-content {
                margin-top: 7rem;
                padding: 1rem;
            }

            .content-box {
                padding: 2rem;
            }

            .table-container {
                margin-top: 1rem;
            }

            th, td {
                padding: 0.5rem;
                font-size: 0.875rem;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <header class="tech-header">
        <div class="header-content">
            <a href="index.php" class="logo-container">
                <img src="images/breathtime-logo.svg" alt="BreathTime" class="logo-img">
                <span class="logo-text">BreathTime</span>
            </a>
            <nav class="tech-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $db = Database::getInstance();
                    $user_avatar = $db->query(
                        "SELECT avatar FROM users WHERE id = ?",
                        [$_SESSION['user_id']]
                    )->fetch()['avatar'] ?? null;
                    ?>
                    <a href="my-petitions.php" class="nav-link">Podpisane petycje</a>
                    <a href="notifications.php" class="nav-link">Powiadomienia</a>
                    <a href="edit-profile.php" class="nav-user">
                        <div class="nav-avatar">
                            <?php if ($user_avatar): ?>
                                <img src="uploads/avatars/<?php echo htmlspecialchars($user_avatar); ?>" 
                                     alt="Avatar użytkownika">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="fa fa-user fa-2x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span>Profil</span>
                    </a>
                    <a href="logout.php" class="nav-link">Wyloguj</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Zaloguj się</a>
                    <a href="register.php" class="nav-link">Zarejestruj się</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <?php echo $content; ?>
    </main>
</body>
</html>
