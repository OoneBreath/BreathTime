<?php
session_start();
require_once '../config/database.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $post_id = $_POST['post_id'] ?? null;
        
        if ($post_id) {
            switch ($_POST['action']) {
                case 'delete':
                    try {
                        $db->query("DELETE FROM posts WHERE id = ?", [$post_id]);
                        $_SESSION['success'] = "Post został usunięty.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas usuwania posta: " . $e->getMessage();
                    }
                    break;
                    
                case 'publish':
                    try {
                        $db->query(
                            "UPDATE posts SET status = 'published' WHERE id = ?",
                            [$post_id]
                        );
                        $_SESSION['success'] = "Post został opublikowany.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas publikowania posta: " . $e->getMessage();
                    }
                    break;
                    
                case 'unpublish':
                    try {
                        $db->query(
                            "UPDATE posts SET status = 'draft' WHERE id = ?",
                            [$post_id]
                        );
                        $_SESSION['success'] = "Post został oznaczony jako szkic.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas wycofywania posta: " . $e->getMessage();
                    }
                    break;
            }
        }
    }
    header('Location: manage-posts.php');
    exit();
}

// Pobierz posty użytkownika
$stmt = $pdo->prepare('
    SELECT p.*, u.username as author_name,
    GROUP_CONCAT(t.name) as tags
    FROM posts p
    LEFT JOIN users u ON p.author_id = u.id
    LEFT JOIN post_tags pt ON p.id = pt.post_id
    LEFT JOIN tags t ON pt.tag_id = t.id
    WHERE p.author_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
');
$stmt->execute([$_SESSION['user_id']]);
$posts = $stmt->fetchAll();
?>

<?php
$page_title = "Zarządzanie postami - Panel administracyjny";
ob_start();
?>

<div class="content-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Zarządzanie postami</h1>
        <a href="create-post.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nowy post
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Tytuł</th>
                    <th>Tagi</th>
                    <th>Status</th>
                    <th>Data utworzenia</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <a href="../blog-post.php?id=<?php echo $post['id']; ?>" target="_blank">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($post['tags'] ?? ''); ?></td>
                        <td>
                            <span class="status-badge <?php echo $post['status']; ?>">
                                <?php
                                echo $post['status'] === 'published' ? 'Opublikowany' : 'Szkic';
                                ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></td>
                        <td class="actions">
                            <?php if ($post['status'] === 'draft'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" name="action" value="publish" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Publikuj
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" name="action" value="unpublish" class="btn btn-warning btn-sm">
                                        <i class="fas fa-ban"></i> Wycofaj
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" class="d-inline" 
                                  onsubmit="return confirm('Czy na pewno chcesz usunąć ten post? Ta operacja jest nieodwracalna.');">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Usuń
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require '../includes/tech_layout.php';
?>
