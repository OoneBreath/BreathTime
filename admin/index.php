<?php
require_once '../includes/config.php';
session_start();

// Sprawdź czy użytkownik jest zalogowany i jest adminem
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance();

// Pobierz statystyki
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'active_users' => $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch()['count'],
    'total_petitions' => $db->query("SELECT COUNT(*) as count FROM petitions")->fetch()['count'],
    'active_petitions' => $db->query("SELECT COUNT(*) as count FROM petitions WHERE status = 'active'")->fetch()['count'],
    'total_signatures' => $db->query("SELECT COUNT(*) as count FROM signatures")->fetch()['count'],
    'total_views' => $db->query("SELECT COUNT(*) as count FROM statistics")->fetch()['count']
];

// Pobierz ostatnie aktywności
$recent_petitions = $db->query(
    "SELECT p.*, u.name as creator_name 
     FROM petitions p 
     JOIN users u ON p.created_by = u.id 
     ORDER BY p.created_at DESC 
     LIMIT 5"
)->fetchAll();

$recent_signatures = $db->query(
    "SELECT s.*, u.name as signer_name, p.title as petition_title 
     FROM signatures s 
     JOIN users u ON s.user_id = u.id 
     JOIN petitions p ON s.petition_id = p.id 
     ORDER BY s.created_at DESC 
     LIMIT 5"
)->fetchAll();

$page_title = "Panel administracyjny";
ob_start();
?>

<div class="content-box">
    <h1>Panel administracyjny</h1>

    <div class="admin-nav mb-4">
        <a href="users.php" class="btn btn-primary">
            <i class="fas fa-users"></i> Zarządzaj użytkownikami
        </a>
        <a href="petitions.php" class="btn btn-primary">
            <i class="fas fa-file-signature"></i> Zarządzaj petycjami
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-title">Użytkownicy</div>
            <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
            <div class="stat-subtitle">Aktywni: <?php echo number_format($stats['active_users']); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Petycje</div>
            <div class="stat-value"><?php echo number_format($stats['total_petitions']); ?></div>
            <div class="stat-subtitle">Aktywne: <?php echo number_format($stats['active_petitions']); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Podpisy</div>
            <div class="stat-value"><?php echo number_format($stats['total_signatures']); ?></div>
            <div class="stat-subtitle">Średnio: <?php 
                echo $stats['total_petitions'] > 0 
                    ? number_format($stats['total_signatures'] / $stats['total_petitions'], 1) 
                    : '0'; 
                ?> na petycję</div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Wyświetlenia</div>
            <div class="stat-value"><?php echo number_format($stats['total_views']); ?></div>
            <div class="stat-subtitle">Konwersja: <?php 
                echo $stats['total_views'] > 0 
                    ? number_format(($stats['total_signatures'] / $stats['total_views']) * 100, 1) . '%'
                    : '0%';
                ?></div>
        </div>
    </div>

    <div class="recent-activities">
        <div class="recent-section">
            <h2>Ostatnie petycje</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tytuł</th>
                            <th>Autor</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_petitions as $petition): ?>
                            <tr>
                                <td>
                                    <a href="../view-petition.php?id=<?php echo $petition['id']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($petition['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($petition['creator_name']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $petition['status']; ?>">
                                        <?php
                                        switch ($petition['status']) {
                                            case 'draft': echo 'Szkic'; break;
                                            case 'pending': echo 'Oczekuje'; break;
                                            case 'active': echo 'Aktywna'; break;
                                            case 'completed': echo 'Zakończona'; break;
                                            case 'blocked': echo 'Zablokowana'; break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($petition['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="recent-section">
            <h2>Ostatnie podpisy</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Użytkownik</th>
                            <th>Petycja</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_signatures as $signature): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($signature['signer_name']); ?></td>
                                <td>
                                    <a href="../view-petition.php?id=<?php echo $signature['petition_id']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($signature['petition_title']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($signature['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Tylko style specyficzne dla strony głównej panelu */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
}

.stat-title {
    font-size: 1.1rem;
    color: var(--text-light);
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 600;
    color: var(--accent);
    margin-bottom: 0.5rem;
}

.stat-subtitle {
    font-size: 0.9rem;
    color: var(--text-light);
}

.recent-activities {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.recent-section h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--text-color);
}
</style>

<?php
$content = ob_get_clean();
require_once 'tech_layout.php';
?>
