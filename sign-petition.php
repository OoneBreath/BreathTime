<?php
require_once 'includes/config.php';
session_start();

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Sprawdzenie czy podano ID petycji
if (!isset($_GET['id'])) {
    header('Location: petitions.php');
    exit();
}

$db = Database::getInstance();

// Pobierz dane petycji
$petition = $db->query(
    "SELECT p.*, u.name as creator_name,
            (SELECT COUNT(*) FROM signatures WHERE petition_id = p.id) as signatures_count
     FROM petitions p
     JOIN users u ON p.created_by = u.id
     WHERE p.id = ? AND p.status = 'active'",
    [$_GET['id']]
)->fetch();

// Sprawdź czy petycja istnieje
if (!$petition) {
    header('Location: petitions.php');
    exit();
}

// Sprawdź czy użytkownik już podpisał petycję
$already_signed = $db->query(
    "SELECT COUNT(*) as count FROM signatures 
     WHERE petition_id = ? AND user_id = ?",
    [$_GET['id'], $_SESSION['user_id']]
)->fetch()['count'] > 0;

if ($already_signed) {
    header('Location: view-petition.php?id=' . $_GET['id']);
    exit();
}

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['comment'] ?? '');
    $public_signature = isset($_POST['public_signature']);
    
    try {
        // Włączamy pełne raportowanie błędów
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Debug: wyświetl dane przed zapytaniem
        error_log("Debug: Petition ID: " . $petition['id']);
        error_log("Debug: User ID: " . $_SESSION['user_id']);
        error_log("Debug: Comment: " . $comment);
        error_log("Debug: Public: " . ($public_signature ? "1" : "0"));
        
        // Pobierz dane użytkownika
        $user = $db->query(
            "SELECT email, name FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        )->fetch();
        
        if (!$user) {
            throw new Exception("Nie znaleziono danych użytkownika");
        }
        
        // Dodaj podpis
        $result = $db->query(
            "INSERT INTO signatures (petition_id, user_id, email, name, comment, public, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $petition['id'], 
                $_SESSION['user_id'], 
                $user['email'],
                $user['name'],
                $comment, 
                $public_signature ? 1 : 0
            ]
        );
        
        error_log("Debug: Signature added successfully");

        // Wyślij powiadomienie do admina
        require_once 'includes/Mailer.php';
        $mailer = new Mailer();
        $mailer->sendAdminNewSignatureNotification($petition['title'], $user['name'], $petition['id']);
        
        // Przekieruj do strony petycji
        header('Location: view-petition.php?id=' . $petition['id'] . '&signed=1');
        exit();
        
    } catch (Exception $e) {
        error_log("Error in sign-petition.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $error = "Wystąpił błąd podczas dodawania podpisu: " . $e->getMessage();
    }
}

$page_title = "Podpisz petycję - " . $petition['title'];
ob_start();
?>

<div class="content-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Podpisz petycję</h1>
        <a href="view-petition.php?id=<?php echo $petition['id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Wróć do petycji
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="petition-info mb-4">
        <h2><?php echo htmlspecialchars($petition['title']); ?></h2>
        <p class="text-muted">
            Autor: <?php echo htmlspecialchars($petition['creator_name']); ?>
        </p>
        <div class="progress-bar mb-2">
            <div class="progress" style="width: <?php 
                echo min(($petition['signatures_count'] / $petition['target_signatures']) * 100, 100);
            ?>%"></div>
        </div>
        <div class="d-flex justify-content-between text-muted">
            <span><?php echo number_format($petition['signatures_count']); ?> podpisów</span>
            <span>Cel: <?php echo number_format($petition['target_signatures']); ?></span>
        </div>
    </div>

    <form method="POST" class="sign-form">
        <div class="form-group mb-3">
            <label for="comment">Komentarz (opcjonalnie):</label>
            <textarea id="comment" name="comment" class="form-control" rows="4"
                      placeholder="Dlaczego podpisujesz tę petycję?"><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
        </div>

        <div class="form-check mb-4">
            <input type="checkbox" class="form-check-input" id="public_signature" 
                   name="public_signature" value="1" 
                   <?php echo isset($_POST['public_signature']) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="public_signature">
                Pokaż mój podpis publicznie na stronie petycji
            </label>
        </div>

        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle"></i>
            <strong>Informacja:</strong> Twój podpis zostanie dodany używając danych z Twojego konta.
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-pen"></i> Podpisz petycję
        </button>
    </form>
</div>

<style>
.petition-info {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.progress-bar {
    height: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.25rem;
    overflow: hidden;
}

.progress-bar .progress {
    height: 100%;
    background: var(--accent);
    border-radius: 0.25rem;
    transition: width 0.3s ease;
}

.sign-form {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.form-control {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid var(--border);
    color: var(--text-color);
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: var(--accent);
    color: var(--text-color);
    box-shadow: none;
}

.form-check-input {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: var(--border);
}

.form-check-input:checked {
    background-color: var(--accent);
    border-color: var(--accent);
}

.alert {
    border: 1px solid var(--border);
}

.alert-info {
    background: rgba(var(--accent-rgb), 0.1);
    border-color: rgba(var(--accent-rgb), 0.2);
}

.alert-danger {
    background: rgba(var(--danger-rgb), 0.1);
    border-color: rgba(var(--danger-rgb), 0.2);
}
</style>

<?php
$content = ob_get_clean();
require 'includes/tech_layout.php';
?>
