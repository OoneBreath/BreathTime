<?php
require_once '../includes/config.php';
session_start();

// Sprawdź czy użytkownik jest zalogowany i jest adminem
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $excerpt = $_POST['excerpt'] ?? '';
    $content = $_POST['content'] ?? '';
    $author_id = $_SESSION['user_id'];
    
    // Obsługa przesyłania obrazka
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/blog/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_url = 'images/blog/' . $filename;
        }
    }
    
    try {
        $db->query(
            "INSERT INTO posts (title, excerpt, content, image_url, author_id, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$title, $excerpt, $content, $image_url, $author_id]
        );
        
        $_SESSION['success'] = "Post został dodany pomyślnie.";
        header('Location: manage-posts.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Wystąpił błąd podczas dodawania posta: " . $e->getMessage();
    }
}

$page_title = "Dodaj nowy post - Panel administracyjny";
ob_start();
?>

<div class="content-box">
    <h1>Dodaj nowy post</h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Tytuł</label>
            <input type="text" id="title" name="title" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="excerpt">Wprowadzenie</label>
            <textarea id="excerpt" name="excerpt" class="form-control" rows="3" required></textarea>
        </div>

        <div class="form-group">
            <label for="content">Treść</label>
            <textarea id="content" name="content" class="form-control" rows="10" required></textarea>
        </div>

        <div class="form-group">
            <label for="image">Zdjęcie</label>
            <input type="file" id="image" name="image" class="form-control" accept="image/*">
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">Dodaj post</button>
            <a href="manage-posts.php" class="btn btn-secondary">Anuluj</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require '../includes/tech_layout.php';
?>
