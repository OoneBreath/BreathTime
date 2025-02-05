<?php
require_once 'includes/config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = $_POST['email'];
        $db = Database::getInstance();
        
        $user = $db->query(
            "SELECT id, name FROM users WHERE email = ?",
            [$email]
        )->fetch();

        if ($user) {
            $reset_token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $db->query(
                "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?",
                [$reset_token, $expires, $user['id']]
            );

            $mailer = new Mailer();
            $reset_url = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $reset_token;
            
            $subject = "Reset hasła w BreathTime";
            $body = "Witaj {$user['name']},\n\n";
            $body .= "Otrzymaliśmy prośbę o reset hasła do Twojego konta. Kliknij poniższy link, aby zresetować hasło:\n\n";
            $body .= $reset_url . "\n\n";
            $body .= "Link jest ważny przez 1 godzinę. Jeśli nie prosiłeś o reset hasła, zignoruj tę wiadomość.\n\n";
            $body .= "Pozdrawiamy,\nZespół BreathTime";
            
            if ($mailer->send($email, $subject, $body)) {
                $success = 'Link do resetowania hasła został wysłany na Twój adres email.';
            } else {
                $error = 'Nie udało się wysłać emaila. Spróbuj ponownie później.';
            }
        } else {
            $success = 'Jeśli podany email istnieje w naszej bazie, wyślemy na niego link do resetowania hasła.';
        }
    } elseif (isset($_POST['password']) && $token) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($password) || empty($confirm_password)) {
            $error = 'Wypełnij wszystkie pola';
        } elseif ($password !== $confirm_password) {
            $error = 'Hasła nie są identyczne';
        } elseif (strlen($password) < 8) {
            $error = 'Hasło musi mieć co najmniej 8 znaków';
        } else {
            $db = Database::getInstance();
            
            $user = $db->query(
                "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()",
                [$token]
            )->fetch();

            if ($user) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $db->query(
                    "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?",
                    [$hashed_password, $user['id']]
                );
                
                $success = 'Hasło zostało zmienione. Możesz się teraz zalogować.';
            } else {
                $error = 'Link do resetowania hasła wygasł lub jest nieprawidłowy.';
            }
        }
    }
}

$page_title = 'Reset hasła - BreathTime';
ob_start(); ?>

<div class="content-box">
    <h1>Reset hasła</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
            <?php if (strpos($success, 'Możesz się teraz zalogować') !== false): ?>
                <p><a href="login.php" class="btn btn-primary">Przejdź do logowania</a></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($token): ?>
        <!-- Formularz zmiany hasła -->
        <form method="POST" action="" autocomplete="on">
            <div class="form-group">
                <label for="password">Nowe hasło</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control" 
                       autocomplete="new-password"
                       required>
                <small class="form-text">Minimum 8 znaków</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Potwierdź nowe hasło</label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       class="form-control" 
                       autocomplete="new-password"
                       required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Zmień hasło</button>
        </form>
    <?php else: ?>
        <!-- Formularz wysyłania linku resetującego -->
        <form method="POST" action="" autocomplete="on">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       autocomplete="email"
                       required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Wyślij link resetujący</button>
        </form>
    <?php endif; ?>
    
    <div class="form-footer">
        <a href="login.php">Powrót do logowania</a>
    </div>
</div>

<style>
.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: var(--text-light);
}
</style>

<?php
$content = ob_get_clean();
require 'includes/tech_layout.php';
?>
