<?php
require_once '../includes/config.php';
session_start();

// Sprawdź czy użytkownik jest zalogowany i jest adminem
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance();

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $petition_id = $_POST['petition_id'] ?? null;
        
        if ($petition_id) {
            switch ($_POST['action']) {
                case 'delete':
                    try {
                        // Najpierw usuń powiązane rekordy
                        $db->query("DELETE FROM signatures WHERE petition_id = ?", [$petition_id]);
                        $db->query("DELETE FROM comments WHERE petition_id = ?", [$petition_id]);
                        
                        // Teraz usuń petycję
                        $db->query("DELETE FROM petitions WHERE id = ?", [$petition_id]);
                        
                        $_SESSION['success'] = "Petycja została usunięta.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas usuwania petycji: " . $e->getMessage();
                    }
                    break;
                    
                case 'approve':
                    try {
                        $db->query(
                            "UPDATE petitions SET status = 'active' WHERE id = ?",
                            [$petition_id]
                        );
                        $_SESSION['success'] = "Petycja została zatwierdzona.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas zatwierdzania petycji: " . $e->getMessage();
                    }
                    break;
                    
                case 'block':
                    try {
                        $db->query(
                            "UPDATE petitions SET status = 'blocked' WHERE id = ?",
                            [$petition_id]
                        );
                        $_SESSION['success'] = "Petycja została zablokowana.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas blokowania petycji: " . $e->getMessage();
                    }
                    break;
            }
        }
    }
    header('Location: petitions.php');
    exit();
}

// Pobierz petycje z bazy
$petitions = $db->query(
    "SELECT p.*, 
            u.name as creator_name,
            COUNT(DISTINCT s.id) as signatures_count,
            COUNT(DISTINCT c.id) as comments_count,
            (SELECT COUNT(*) FROM statistics WHERE page_url = CONCAT('view-petition.php?id=', p.id)) as views_count
     FROM petitions p
     JOIN users u ON p.created_by = u.id
     LEFT JOIN signatures s ON p.id = s.petition_id
     LEFT JOIN comments c ON p.id = c.petition_id
     GROUP BY p.id
     ORDER BY p.created_at DESC"
)->fetchAll();

$page_title = "Zarządzanie petycjami - Panel administracyjny";
ob_start();
?>

<div class="content-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Zarządzanie petycjami</h1>
        <a href="../create-petition.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nowa petycja
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

    <div class="table-responsive">
        <table class="table petition-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th style="min-width: 250px;">Tytuł</th>
                    <th>Autor</th>
                    <th>Status</th>
                    <th class="text-center">Statystyki</th>
                    <th>Data utworzenia</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($petitions as $petition): ?>
                    <tr>
                        <td class="petition-id">
                            <?php echo $petition['id']; ?>
                        </td>
                        <td>
                            <a href="../view-petition.php?id=<?php echo $petition['id']; ?>" 
                               class="petition-title" target="_blank">
                                <?php echo htmlspecialchars($petition['title']); ?>
                            </a>
                        </td>
                        <td>
                            <span class="creator-name">
                                <?php echo htmlspecialchars($petition['creator_name']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $petition['status']; ?>">
                                <?php
                                switch ($petition['status']) {
                                    case 'draft':
                                        echo '<i class="fas fa-pencil-alt"></i> Szkic';
                                        break;
                                    case 'pending':
                                        echo '<i class="fas fa-clock"></i> Oczekuje';
                                        break;
                                    case 'active':
                                        echo '<i class="fas fa-check-circle"></i> Aktywna';
                                        break;
                                    case 'completed':
                                        echo '<i class="fas fa-flag-checkered"></i> Zakończona';
                                        break;
                                    case 'blocked':
                                        echo '<i class="fas fa-ban"></i> Zablokowana';
                                        break;
                                }
                                ?>
                            </span>
                        </td>
                        <td>
                            <div class="stats-container">
                                <div class="stat-item">
                                    <i class="fas fa-signature"></i>
                                    <span class="stat-value"><?php echo number_format($petition['signatures_count']); ?></span>
                                    <span class="stat-label">podpisów</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-eye"></i>
                                    <span class="stat-value"><?php echo number_format($petition['views_count']); ?></span>
                                    <span class="stat-label">wyświetleń</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-chart-line"></i>
                                    <span class="stat-value">
                                        <?php
                                        if ($petition['views_count'] > 0) {
                                            echo round(($petition['signatures_count'] / $petition['views_count']) * 100, 1) . '%';
                                        } else {
                                            echo '0%';
                                        }
                                        ?>
                                    </span>
                                    <span class="stat-label">konwersja</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="date-info">
                                <div class="date"><?php echo date('d.m.Y', strtotime($petition['created_at'])); ?></div>
                                <div class="time"><?php echo date('H:i', strtotime($petition['created_at'])); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($petition['status'] === 'pending'): ?>
                                    <button type="button" class="action-btn approve" 
                                            onclick="confirmAction('approve', <?php echo $petition['id']; ?>)"
                                            title="Zatwierdź petycję">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($petition['status'] !== 'blocked'): ?>
                                    <button type="button" class="action-btn block" 
                                            onclick="confirmAction('block', <?php echo $petition['id']; ?>)"
                                            title="Zablokuj petycję">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" class="action-btn delete" 
                                        onclick="confirmAction('delete', <?php echo $petition['id']; ?>)"
                                        title="Usuń petycję">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.petition-table {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 4px;
    margin-top: 20px;
}

.petition-table th {
    background: rgba(255, 255, 255, 0.1);
    padding: 12px;
    font-weight: 500;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 0.9);
    text-transform: uppercase;
    font-size: 0.85em;
    letter-spacing: 0.5px;
}

.petition-table td {
    padding: 16px 12px;
    vertical-align: middle;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.petition-id {
    font-family: monospace;
    color: #a0aec0;
}

.petition-title {
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    display: block;
    transition: color 0.2s;
}

.petition-title:hover {
    color: #3498db;
}

.creator-name {
    color: #a0aec0;
    font-size: 0.9em;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: 500;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
}

.status-badge i {
    font-size: 0.9em;
}

.status-draft {
    background: linear-gradient(135deg, rgba(127, 140, 141, 0.2), rgba(127, 140, 141, 0.3));
    border-color: rgba(127, 140, 141, 0.4);
    color: #95a5a6;
}

.status-pending {
    background: linear-gradient(135deg, rgba(241, 196, 15, 0.2), rgba(241, 196, 15, 0.3));
    border-color: rgba(241, 196, 15, 0.4);
    color: #f1c40f;
}

.status-active {
    background: linear-gradient(135deg, rgba(46, 204, 113, 0.2), rgba(46, 204, 113, 0.3));
    border-color: rgba(46, 204, 113, 0.4);
    color: #2ecc71;
}

.status-completed {
    background: linear-gradient(135deg, rgba(52, 152, 219, 0.2), rgba(52, 152, 219, 0.3));
    border-color: rgba(52, 152, 219, 0.4);
    color: #3498db;
}

.status-blocked {
    background: linear-gradient(135deg, rgba(231, 76, 60, 0.2), rgba(231, 76, 60, 0.3));
    border-color: rgba(231, 76, 60, 0.4);
    color: #e74c3c;
}

.stats-container {
    display: flex;
    gap: 16px;
    justify-content: center;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.stat-item i {
    font-size: 0.9em;
    color: #a0aec0;
    margin-bottom: 2px;
}

.stat-value {
    font-weight: 600;
    color: #fff;
}

.stat-label {
    font-size: 0.8em;
    color: #a0aec0;
}

.date-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.date {
    color: #fff;
    font-weight: 500;
}

.time {
    color: #a0aec0;
    font-size: 0.9em;
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    border: none;
    border-radius: 4px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.action-btn.approve {
    background: linear-gradient(135deg, rgba(46, 204, 113, 0.2), rgba(46, 204, 113, 0.3));
    border-color: rgba(46, 204, 113, 0.4);
    color: #2ecc71;
}

.action-btn.block {
    background: linear-gradient(135deg, rgba(241, 196, 15, 0.2), rgba(241, 196, 15, 0.3));
    border-color: rgba(241, 196, 15, 0.4);
    color: #f1c40f;
}

.action-btn.delete {
    background: linear-gradient(135deg, rgba(231, 76, 60, 0.2), rgba(231, 76, 60, 0.3));
    border-color: rgba(231, 76, 60, 0.4);
    color: #e74c3c;
}
</style>

<script>
function confirmAction(action, petitionId) {
    let message = '';
    switch(action) {
        case 'approve':
            message = 'Czy na pewno chcesz zatwierdzić tę petycję?';
            break;
        case 'block':
            message = 'Czy na pewno chcesz zablokować tę petycję?';
            break;
        case 'delete':
            message = 'Czy na pewno chcesz usunąć tę petycję? Ta operacja jest nieodwracalna.';
            break;
    }

    if (confirm(message)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;

        const petitionIdInput = document.createElement('input');
        petitionIdInput.type = 'hidden';
        petitionIdInput.name = 'petition_id';
        petitionIdInput.value = petitionId;

        form.appendChild(actionInput);
        form.appendChild(petitionIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
$content = ob_get_clean();
require_once 'tech_layout.php';
?>
