<?php
require_once 'includes/config.php';
session_start();

$db = Database::getInstance();

// Sprawdź czy podano ID petycji
if (!isset($_GET['id'])) {
    header('Location: petitions.php');
    exit();
}

// Pobierz dane petycji
$petition = $db->query(
    "SELECT p.*, u.name as creator_name,
            (SELECT COUNT(*) FROM signatures WHERE petition_id = p.id) as signatures_count,
            p.addressee
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

// Sprawdź czy użytkownik podpisał petycję
$signed = false;
if (isset($_SESSION['user_id'])) {
    $signed = $db->query(
        "SELECT COUNT(*) as count FROM signatures 
         WHERE petition_id = ? AND user_id = ?",
        [$_GET['id'], $_SESSION['user_id']]
    )->fetch()['count'] > 0;
}

// Pobierz ostatnie podpisy
$signatures = $db->query(
    "SELECT s.*, u.name, u.avatar 
     FROM signatures s
     JOIN users u ON s.user_id = u.id
     WHERE s.petition_id = ? AND s.public = 1
     ORDER BY s.created_at DESC
     LIMIT 10",
    [$petition['id']]
)->fetchAll();

// Dodaj statystykę wyświetlenia
$db->query(
    "INSERT INTO statistics (page_url) VALUES (?)",
    ['view-petition.php?id=' . $petition['id']]
);

$page_title = $petition['title'] . " - BreathTime";
ob_start();
?>

<div class="content-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo htmlspecialchars($petition['title']); ?></h1>
        <a href="petitions.php" class="btn btn-info">
            <i class="fas fa-arrow-left"></i> Wróć do listy
        </a>
    </div>

    <?php if (isset($_GET['signed'])): ?>
        <div class="alert alert-success mb-4">
            <i class="fas fa-check-circle"></i>
            Dziękujemy za podpisanie petycji!
        </div>
    <?php endif; ?>

    <div class="petition-details mb-4">
        <div class="petition-meta mb-3">
            <span class="status-badge">
                <?php echo htmlspecialchars($petition['category']); ?>
            </span>
            <p class="text-muted mt-2 mb-0">
                Autor: <?php echo htmlspecialchars($petition['creator_name']); ?>
                <br>
                Data utworzenia: <?php echo date('d.m.Y', strtotime($petition['created_at'])); ?>
            </p>
        </div>

        <div class="petition-addressee mb-3">
            <h4>Adresat petycji:</h4>
            <p><?php echo nl2br(htmlspecialchars($petition['addressee'])); ?></p>
        </div>

        <div class="petition-progress mb-4">
            <?php
            $progress = ($petition['signatures_count'] / $petition['target_signatures']) * 100;
            $progress = min(100, $progress);
            ?>
            <div class="progress-bar">
                <div class="progress" style="width: <?php echo $progress; ?>%"></div>
            </div>
            <div class="d-flex justify-content-between text-muted">
                <span><?php echo number_format($petition['signatures_count']); ?> podpisów</span>
                <span>Cel: <?php echo number_format($petition['target_signatures']); ?></span>
            </div>
        </div>

        <div class="petition-content">
            <h2>O petycji</h2>
            <div class="petition-description">
                <?php echo nl2br(htmlspecialchars($petition['description'])); ?>
            </div>
        </div>

        <div class="petition-actions mt-4">
            <?php if (!$signed && isset($_SESSION['user_id'])): ?>
                <a href="sign-petition.php?id=<?php echo $petition['id']; ?>" 
                   class="btn btn-primary">
                    <i class="fas fa-pen"></i> Podpisz petycję
                </a>
            <?php elseif ($signed): ?>
                <button class="btn btn-success" disabled>
                    <i class="fas fa-check"></i> Podpisano
                </button>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Zaloguj się, aby podpisać
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="signatures-section">
                <h2>Ostatnie podpisy</h2>
                <?php if ($signatures): ?>
                    <div class="signatures-list">
                        <?php foreach ($signatures as $signature): ?>
                            <div class="signature-item">
                                <div class="signature-info d-flex align-items-center">
                                    <div class="signature-avatar me-3">
                                        <?php if ($signature['avatar']): ?>
                                            <img src="uploads/avatars/<?php echo htmlspecialchars($signature['avatar']); ?>" 
                                                 alt="Avatar użytkownika" class="rounded-circle" width="50" height="50">
                                        <?php else: ?>
                                            <i class="fas fa-user-circle fa-3x"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($signature['name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($signature['created_at'])); ?>
                                        </small>
                                        <?php if ($signature['comment']): ?>
                                            <p class="signature-comment mt-2">
                                                <?php echo htmlspecialchars($signature['comment']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Brak publicznych podpisów.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.petition-details {
    background: rgba(255, 255, 255, 0.05);
    padding: 20px;
    border-radius: 8px;
}

.petition-description {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1rem;
    margin: 1rem 0;
    white-space: pre-line;
}

.signatures-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.signature-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.signature-item:last-child {
    margin-bottom: 0;
}

.signature-info {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
}

.signature-avatar {
    margin-right: 15px;
    flex-shrink: 0;
}

.signature-avatar img {
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    object-fit: cover;
}

.signature-avatar i {
    color: #ccc;
}

.signature-comment {
    margin: 0;
    color: var(--text-light);
}

.alert {
    border: 1px solid var(--border);
}

.alert-success {
    background: rgba(var(--success-rgb), 0.1);
    border-color: rgba(var(--success-rgb), 0.2);
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-primary {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
    color: white;
}

.btn-info:hover {
    background-color: #138496;
    border-color: #117a8b;
}

.btn i {
    margin-right: 0.5rem;
}
</style>

<?php
$content = ob_get_clean();
require 'includes/tech_layout.php';
?>
