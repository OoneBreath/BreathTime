<?php
require_once 'includes/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

// Pobierz petycje podpisane przez użytkownika
$petitions = $db->query(
    "SELECT p.*, 
            COUNT(DISTINCT s2.id) as signatures_count,
            COUNT(DISTINCT c.id) as comments_count,
            (SELECT COUNT(*) FROM statistics WHERE page_url = CONCAT('view-petition.php?id=', p.id)) as views_count
     FROM petitions p
     INNER JOIN signatures s ON p.id = s.petition_id AND s.user_id = ?
     LEFT JOIN signatures s2 ON p.id = s2.petition_id
     LEFT JOIN comments c ON p.id = c.petition_id
     GROUP BY p.id
     ORDER BY p.created_at DESC",
    [$_SESSION['user_id']]
)->fetchAll();

$page_title = 'Podpisane petycje - BreathTime';
ob_start(); ?>

<div class="content-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Podpisane petycje</h1>
        <a href="petitions.php" class="btn btn-primary btn-lg">
            <i class="fas fa-search"></i> Przeglądaj petycje
        </a>
    </div>
    
    <?php if (empty($petitions)): ?>
        <div class="alert alert-info text-center p-4" style="font-size: 1.2em;">
            <i class="fas fa-info-circle fa-2x mb-3"></i><br>
            Nie podpisałeś jeszcze żadnych petycji
        </div>
    <?php else: ?>
        <div class="petitions-grid">
            <?php foreach ($petitions as $petition): ?>
                <div class="petition-card">
                    <div class="petition-header">
                        <h3><?php echo htmlspecialchars($petition['title']); ?></h3>
                        <span class="status-badge <?php echo $petition['status']; ?>">
                            <?php
                            switch ($petition['status']) {
                                case 'draft':
                                    echo 'Szkic';
                                    break;
                                case 'pending':
                                    echo 'Oczekuje';
                                    break;
                                case 'active':
                                    echo 'Aktywna';
                                    break;
                                case 'completed':
                                    echo 'Zakończona';
                                    break;
                                case 'rejected':
                                    echo 'Odrzucona';
                                    break;
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="petition-body">
                        <p><?php echo nl2br(htmlspecialchars(mb_substr($petition['description'], 0, 150) . '...')); ?></p>
                        
                        <div class="petition-stats">
                            <div class="stat">
                                <i class="fas fa-signature"></i>
                                <?php echo $petition['signatures_count']; ?> podpisów
                            </div>
                            <div class="stat">
                                <i class="fas fa-eye"></i>
                                <?php echo $petition['views_count']; ?> wyświetleń
                            </div>
                            <div class="stat">
                                <i class="fas fa-chart-line"></i>
                                <?php 
                                    $conversion = $petition['views_count'] > 0 ? 
                                        round(($petition['signatures_count'] / $petition['views_count']) * 100, 1) : 0;
                                    echo $conversion . '%';
                                ?> konwersji
                            </div>
                        </div>
                    </div>
                    
                    <div class="petition-footer">
                        <a href="view-petition.php?id=<?php echo $petition['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Zobacz szczegóły
                        </a>
                        <?php if ($petition['status'] === 'active'): ?>
                            <a href="share-petition.php?id=<?php echo $petition['id']; ?>" class="btn btn-success">
                                <i class="fas fa-share"></i> Udostępnij
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.petitions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.petition-card {
    background: var(--secondary-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.petition-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
}

.petition-header {
    margin-bottom: 1.2rem;
    border-bottom: 1px solid var(--border);
    padding-bottom: 1rem;
}

.petition-header h3 {
    font-size: 1.4rem;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 0.8rem;
}

.petition-stats {
    display: flex;
    justify-content: space-between;
    margin: 1.5rem 0;
    padding: 1rem;
    background: var(--glass-effect);
    border-radius: 8px;
    border: 1px solid var(--glass-border);
}

.petition-stats .stat {
    text-align: center;
    flex: 1;
    padding: 0.5rem;
    font-size: 1.1rem;
}

.petition-stats .stat i {
    font-size: 1.2rem;
    margin-right: 0.5rem;
    color: var(--accent);
}

.petition-footer {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
}

.petition-footer .btn {
    flex: 1;
    padding: 0.8rem 1.2rem;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.status-badge {
    display: inline-block;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.active {
    background: var(--success-light);
    color: var(--success);
    border: 1px solid var(--success-border);
}

.status-badge.completed {
    background: var(--warning-light);
    color: var(--warning);
    border: 1px solid var(--warning-border);
}

.status-badge.rejected {
    background: var(--danger-light);
    color: var(--danger);
    border: 1px solid var(--danger-border);
}

.alert-info {
    background: var(--glass-effect);
    border: 1px solid var(--glass-border);
    color: var(--text-color);
}

.btn-lg {
    padding: 0.8rem 1.5rem;
    font-size: 1.1rem;
}

.d-inline {
    display: inline-block;
}

.mb-4 {
    margin-bottom: 1.5rem;
}

.d-flex {
    display: flex;
}

.justify-content-between {
    justify-content: space-between;
}

.align-items-center {
    align-items: center;
}

.btn {
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn i {
    font-size: 0.9em;
}

.btn-primary {
    background: rgba(255, 200, 0, 0.2);
    color: #ffc800;
}

.btn-primary:hover {
    background: rgba(255, 200, 0, 0.3);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
}

.btn-success {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
}

.btn-success:hover {
    background: rgba(40, 167, 69, 0.3);
}
</style>

<?php
$content = ob_get_clean();
require 'includes/tech_layout.php';
?>
