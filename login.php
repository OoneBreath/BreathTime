<?php
require_once 'includes/config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Wypełnij wszystkie pola';
    } else {
        $db = Database::getInstance();
        $user = $db->query(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        )->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'unverified') {
                $error = 'Twoje konto nie zostało jeszcze zweryfikowane. Sprawdź swoją skrzynkę email i kliknij w link aktywacyjny.';
            } else if ($user['status'] === 'suspended') {
                $error = 'Twoje konto zostało zawieszone. Skontaktuj się z administratorem.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                
                if ($remember) {
                    setcookie('remember_email', $email, time() + (86400 * 30), '/');
                }
                
                // Zapisz IP ostatniego logowania
                try {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
                    $db->query(
                        "UPDATE users SET last_login_ip = ? WHERE id = ?",
                        [$ip, $user['id']]
                    );
                } catch (PDOException $e) {
                    // Ignorujemy błąd aktualizacji IP - nie jest to krytyczne
                    error_log("Nie udało się zaktualizować IP: " . $e->getMessage());
                }
                
                header('Location: index.php');
                exit();
            }
        } else {
            $error = 'Nieprawidłowy email lub hasło';
        }
    }
}

// Sprawdź czy jest zapisany email
$saved_email = $_COOKIE['remember_email'] ?? '';

$page_title = 'Logowanie - BreathTime';
ob_start(); 
?>

<div class="content-box" style="max-width: 400px; margin: 2rem auto;">
    <h1 class="text-center mb-4">Logowanie</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" class="mb-3">
        <div class="form-group mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   class="form-control"
                   value="<?php echo htmlspecialchars($saved_email ?: ($_POST['email'] ?? '')); ?>"
                   required>
        </div>
        
        <div class="form-group mb-4">
            <label for="password" class="form-label">Hasło</label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   class="form-control" 
                   required>
        </div>

        <div class="form-group mb-4">
            <label class="checkbox-label">
                <input type="checkbox" 
                       name="remember" 
                       <?php echo $saved_email ? 'checked' : ''; ?>>
                Zapamiętaj mnie
            </label>
        </div>
        
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-block">
                Zaloguj się
            </button>
        </div>
        
        <div class="text-center mt-4">
            <p class="mb-2">Nie masz jeszcze konta?</p>
            <a href="register.php" class="btn btn-primary">Zarejestruj się</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require 'includes/tech_layout.php';
?>
