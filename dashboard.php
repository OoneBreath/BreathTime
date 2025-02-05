<?php
require_once 'includes/config.php';
session_start();

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

// Pobierz dane użytkownika
$user = $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();

// Pobierz podpisane petycje
$signatures = $db->query(
    "SELECT p.title, s.created_at 
     FROM signatures s 
     JOIN petitions p ON s.petition_id = p.id 
     WHERE s.email = ?
     ORDER BY s.created_at DESC",
    [$user['email']]
)->fetchAll();

// Sprawdź status newslettera
$newsletter = $db->query(
    "SELECT * FROM subscribers WHERE email = ?",
    [$user['email']]
)->fetch();

$page_title = "Panel użytkownika - BreathTime";
ob_start();
?>

<div class="content-box">
    <div class="dashboard-header">
        <h1>Witaj, <?php echo htmlspecialchars($user['name']); ?>!</h1>
        <div class="dashboard-buttons">
            <a href="edit-profile.php" class="btn btn-primary">Edytuj profil</a>
            <a href="logout.php" class="btn btn-warning">Wyloguj się</a>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h2>Twoje dane</h2>
            <div class="info-item">
                <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
            </div>
            <div class="info-item">
                <strong>Data dołączenia:</strong> <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
            </div>
            <div class="info-item">
                <strong>Status:</strong> <?php echo $user['role'] === 'admin' ? 'Administrator' : 'Użytkownik'; ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <br><br>
                    <a href="admin/" class="btn btn-primary">Panel Administracyjny</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-card">
            <h2>Twoje petycje</h2>
            <?php if ($signatures): ?>
                <ul class="petition-list">
                    <?php foreach ($signatures as $signature): ?>
                        <li class="petition-item">
                            <?php echo htmlspecialchars($signature['title']); ?>
                            <br>
                            <small>Podpisano: <?php echo date('d.m.Y', strtotime($signature['created_at'])); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="info-item">Nie podpisałeś jeszcze żadnej petycji.</p>
            <?php endif; ?>
        </div>

        <div class="dashboard-card">
            <h2>Newsletter</h2>
            <div class="info-item">
                <?php if ($newsletter): ?>
                    <strong>Status:</strong> <?php echo $newsletter['status'] === 'active' ? 'Aktywny' : 'Nieaktywny'; ?>
                    <?php if ($newsletter['status'] === 'active'): ?>
                        <br>
                        <small>Zapisano: <?php echo date('d.m.Y', strtotime($newsletter['created_at'])); ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Nie jesteś zapisany do newslettera.</p>
                    <a href="newsletter.php" class="btn btn-primary">Zapisz się</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Tylko style specyficzne dla dashboardu */
.dashboard-header {
    text-align: center;
    margin-bottom: 3rem;
}

.dashboard-buttons {
    margin-top: 1rem;
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.dashboard-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.dashboard-card h2 {
    color: var(--text-color);
    font-size: 1.5em;
    margin-bottom: 1rem;
}

.info-item {
    margin-bottom: 1rem;
    color: var(--text-light);
}

.info-item strong {
    color: var(--text-color);
}

.petition-list {
    list-style: none;
    padding: 0;
}

.petition-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border);
    color: var(--text-light);
}

.petition-item:last-child {
    border-bottom: none;
}

@media (max-width: 768px) {
    .dashboard-grid {
        gap: 1rem;
    }

    .dashboard-card {
        padding: 1rem;
    }
}
</style>

<?php
$content = ob_get_clean();
require 'includes/tech_layout.php';
?>
