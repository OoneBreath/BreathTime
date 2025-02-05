<?php
if (!isset($page_title)) {
    $page_title = 'Panel administracyjny - BreathTime';
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --background: #1a1a1a;
            --background-light: #2a2a2a;
            --text-color: #ffffff;
            --text-light: rgba(255, 255, 255, 0.7);
            --accent: #ffc800;
            --accent-rgb: 255, 200, 0;
            --border: rgba(255, 255, 255, 0.1);
            --success-rgb: 40, 167, 69;
            --danger-rgb: 220, 53, 69;
            --warning-rgb: 255, 193, 7;
        }

        body {
            background: var(--background);
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: var(--background-light);
            border-bottom: 1px solid var(--border);
            padding: 1rem;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.3));
        }

        .navbar-brand img {
            height: 6rem;
            width: auto;
            transition: transform 0.3s ease;
            margin: -0.75rem 0;
            filter: brightness(0) invert(1);  /* To zmieni kolor na biały */
        }

        .navbar-brand:hover img {
            transform: scale(1.05);
        }

        .navbar-dark .navbar-nav .nav-link {
            color: var(--text-light);
            padding: 0.5rem 1rem;
            transition: color 0.3s;
        }

        .navbar-dark .navbar-nav .nav-link:hover,
        .navbar-dark .navbar-nav .nav-link.active {
            color: var(--accent);
        }

        .content-box {
            background: var(--background-light);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 2rem;
            margin: 2rem auto;
            max-width: 1200px;
            width: 95%;
        }

        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--background);
        }

        .btn-primary:hover {
            background: #e6b500;
            border-color: #e6b500;
            color: var(--background);
        }

        .btn-secondary {
            background: var(--background);
            border-color: var(--border);
            color: var(--text-color);
        }

        .btn-secondary:hover {
            background: var(--background-light);
            border-color: var(--accent);
            color: var(--accent);
        }

        .text-muted {
            color: var(--text-light) !important;
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .alert {
            background: var(--background-light);
            border: 1px solid var(--border);
            color: var(--text-color);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../">
                <img src="../images/breathtime-logo.svg" alt="BreathTime Logo">
                <span>Panel administracyjny</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Panel główny
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Użytkownicy
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="petitions.php">
                            <i class="fas fa-file-signature"></i> Petycje
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../">
                            <i class="fas fa-arrow-left"></i> Powrót do strony
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1">
        <?php echo $content; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
