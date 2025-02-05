<?php
require_once 'includes/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

// Sprawdź, czy petycja istnieje i czy należy do zalogowanego użytkownika
if (!isset($_GET['id'])) {
    header('Location: my-petitions.php');
    exit();
}

$petition = $db->query(
    "SELECT * FROM petitions WHERE id = ? AND created_by = ? AND status = 'draft'",
    [$_GET['id'], $_SESSION['user_id']]
)->fetch();

if (!$petition) {
    $_SESSION['error'] = 'Nie znaleziono petycji lub nie masz uprawnień do jej edycji.';
    header('Location: my-petitions.php');
    exit();
}

// Obsługa formularza edycji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $target = (int)$_POST['target'];
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Tytuł jest wymagany.';
    }
    if (empty($description)) {
        $errors[] = 'Opis jest wymagany.';
    }
    if ($target < 100) {
        $errors[] = 'Cel musi wynosić co najmniej 100 podpisów.';
    }
    
    if (empty($errors)) {
        $db->query(
            "UPDATE petitions SET title = ?, description = ?, target = ? WHERE id = ? AND created_by = ? AND status = 'draft'",
            [$title, $description, $target, $_GET['id'], $_SESSION['user_id']]
        );
        
        $_SESSION['success'] = 'Petycja została zaktualizowana.';
        header('Location: my-petitions.php');
        exit();
    }
}

$page_title = 'Edycja petycji - BreathTime';
ob_start();
?>

<div class="content-box">
    <h1>Edycja petycji</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="petition-form">
        <div class="form-group">
            <label for="title">Tytuł petycji</label>
            <input type="text" class="form-control" id="title" name="title" 
                   value="<?php echo htmlspecialchars($petition['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Opis petycji</label>
            <textarea class="form-control" id="description" name="description" 
                      rows="10" required><?php echo htmlspecialchars($petition['description']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="target">Cel (liczba podpisów)</label>
            <input type="number" class="form-control" id="target" name="target" 
                   value="<?php echo htmlspecialchars($petition['target']); ?>" min="100" required>
        </div>
        
        <div class="form-buttons">
            <a href="my-petitions.php" class="btn btn-secondary">Anuluj</a>
            <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>
