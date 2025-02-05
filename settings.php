<?php
session_start();
require_once 'includes/config.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Pobierz dane użytkownika
$db = Database::getInstance();
$user = $db->query(
    "SELECT * FROM users WHERE id = ?",
    [$_SESSION['user_id']]
)->fetch();

// Obsługa formularza
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';

    try {
        // Aktualizacja nazwy i emaila
        if ($name && $email) {
            $db->query(
                "UPDATE users SET name = ?, email = ? WHERE id = ?",
                [$name, $email, $_SESSION['user_id']]
            );
            $success = 'Dane zostały zaktualizowane.';
        }

        // Zmiana hasła
        if ($current_password && $new_password) {
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception('Nieprawidłowe obecne hasło.');
            }
            if ($new_password !== $new_password_confirm) {
                throw new Exception('Nowe hasła nie są identyczne.');
            }
            if (strlen($new_password) < 8) {
                throw new Exception('Nowe hasło musi mieć co najmniej 8 znaków.');
            }

            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $db->query(
                "UPDATE users SET password = ? WHERE id = ?",
                [$password_hash, $_SESSION['user_id']]
            );
            $success = 'Hasło zostało zmienione.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = "Ustawienia - BreathTime";
ob_start();
?>

<div class="content-box">
    <h1>Ustawienia</h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="settings-form">
        <div class="section-box mb-4">
            <h2>Dane podstawowe</h2>
            <div class="form-group">
                <label for="name">Nazwa</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
        </div>
        
        <div class="section-box">
            <h2>Zmiana hasła</h2>
            <div class="form-group">
                <label for="current_password">Obecne hasło</label>
                <input type="password" id="current_password" name="current_password" class="form-control">
            </div>
            <div class="form-group">
                <label for="new_password">Nowe hasło</label>
                <input type="password" id="new_password" name="new_password" class="form-control">
            </div>
            <div class="form-group">
                <label for="new_password_confirm">Potwierdź nowe hasło</label>
                <input type="password" id="new_password_confirm" name="new_password_confirm" class="form-control">
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
        </div>
    </form>
</div>

<style>
.section-box {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.section-box h2 {
    color: var(--text-color);
    font-size: 1.5em;
    margin-bottom: 1.5rem;
}

.settings-form {
    max-width: 600px;
    margin: 0 auto;
}

@media (max-width: 768px) {
    .section-box {
        padding: 1rem;
    }
}
</style>

<?php
$content = ob_get_clean();
require 'includes/tech_layout.php';
?>
