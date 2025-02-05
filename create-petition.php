<?php
require_once 'includes/config.php';
session_start();

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

// Sprawdź czy użytkownik jest administratorem
$user = $db->query(
    "SELECT is_admin FROM users WHERE id = ?",
    [$_SESSION['user_id']]
)->fetch();

if (!$user || !$user['is_admin']) {
    header('Location: petitions.php');
    exit();
}

// Pobierz kategorie
$categories = $db->query("SELECT DISTINCT category FROM petitions WHERE category IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $target = intval($_POST['target'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $new_category = trim($_POST['new_category'] ?? '');
    $addressee = trim($_POST['addressee'] ?? '');
    
    // Walidacja
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Tytuł jest wymagany";
    } elseif (strlen($title) > 255) {
        $errors[] = "Tytuł jest za długi (maksymalnie 255 znaków)";
    }
    
    if (empty($description)) {
        $errors[] = "Opis jest wymagany";
    }
    
    if (empty($addressee)) {
        $errors[] = "Adresat petycji jest wymagany";
    }
    
    if ($target < 100) {
        $errors[] = "Cel musi wynosić co najmniej 100 podpisów";
    }
    
    // Użyj nowej kategorii, jeśli została podana
    if (!empty($new_category)) {
        $category = $new_category;
    } elseif (empty($category)) {
        $errors[] = "Wybierz kategorię lub utwórz nową";
    }
    
    if (empty($errors)) {
        try {
            $db->query(
                "INSERT INTO petitions (title, description, addressee, target_signatures, category, created_by, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())",
                [$title, $description, $addressee, $target, $category, $_SESSION['user_id']]
            );
            
            $petition_id = $db->lastInsertId();
            header('Location: view-petition.php?id=' . $petition_id . '&created=1');
            exit();
            
        } catch (Exception $e) {
            $errors[] = "Wystąpił błąd podczas tworzenia petycji. Spróbuj ponownie później.";
        }
    }
}

$page_title = "Utwórz nową petycję";
ob_start();
?>

<div class="content-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Utwórz nową petycję</h1>
        <a href="petitions.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Wróć do listy
        </a>
    </div>

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
        <div class="form-group mb-4">
            <label for="title">Tytuł petycji:</label>
            <input type="text" id="title" name="title" class="form-control"
                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                   placeholder="Krótki, chwytliwy tytuł petycji">
            <small class="form-text text-muted">Maksymalnie 255 znaków</small>
        </div>

        <div class="form-group mb-4">
            <label for="description">Opis petycji:</label>
            <textarea id="description" name="description" class="form-control" rows="10"
                      placeholder="Szczegółowo opisz cel petycji i dlaczego jest ważna"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>

        <div class="form-group mb-4">
            <label for="addressee">Adresat petycji:</label>
            <input type="text" id="addressee" name="addressee" class="form-control"
                   value="<?php echo isset($_POST['addressee']) ? htmlspecialchars($_POST['addressee']) : ''; ?>"
                   placeholder="Np. Minister Środowiska, Prezydent Miasta, itp.">
            <small class="form-text text-muted">Określ, do kogo kierowana jest ta petycja</small>
        </div>

        <div class="form-group mb-4">
            <label for="target">Cel (liczba podpisów):</label>
            <input type="number" id="target" name="target" class="form-control"
                   value="<?php echo isset($_POST['target']) ? intval($_POST['target']) : 1000; ?>"
                   min="100" step="100">
            <small class="form-text text-muted">Minimalna liczba to 100 podpisów</small>
        </div>

        <div class="category-section mb-4">
            <div class="form-group">
                <label for="category">Kategoria:</label>
                <select id="category" name="category" class="form-control">
                    <option value="">Wybierz kategorię</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"
                                <?php echo (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mt-3">
                <label for="new_category">Lub utwórz nową kategorię:</label>
                <input type="text" id="new_category" name="new_category" class="form-control"
                       value="<?php echo isset($_POST['new_category']) ? htmlspecialchars($_POST['new_category']) : ''; ?>"
                       placeholder="Nazwa nowej kategorii">
            </div>
        </div>

        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle"></i>
            <strong>Informacja:</strong> Twoja petycja zostanie sprawdzona przez moderatora przed publikacją.
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus"></i> Utwórz petycję
        </button>
    </form>
</div>

<style>
.petition-form {
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

.form-text {
    color: var(--text-light);
}

.category-section {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1rem;
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
