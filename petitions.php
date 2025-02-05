<?php
require_once 'includes/config.php';
session_start();

$db = Database::getInstance();

// Parametry filtrowania i sortowania
$sort = $_GET['sort'] ?? 'newest';
$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Podstawowe warunki WHERE
$where_conditions = ["p.status = 'active'"];
$params = [];

// Dodaj filtr kategorii
if ($category !== 'all') {
    $where_conditions[] = "p.category = ?";
    $params[] = $category;
}

// Dodaj wyszukiwanie
if ($search) {
    $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Pobierz całkowitą liczbę wyników
$count_sql = "SELECT COUNT(DISTINCT p.id) as total 
              FROM petitions p 
              JOIN users u ON p.created_by = u.id 
              WHERE " . $where_clause;
$total_petitions = $db->query($count_sql, $params)->fetch()['total'];
$total_pages = ceil($total_petitions / $per_page);

// Główne zapytanie
$sql = "SELECT p.*, u.name as creator_name,
        (SELECT COUNT(*) FROM signatures s WHERE s.petition_id = p.id) as signatures_count
        FROM petitions p
        JOIN users u ON p.created_by = u.id
        WHERE " . $where_clause;

// Dodaj sortowanie
switch ($sort) {
    case 'popular':
        $sql .= " ORDER BY signatures_count DESC";
        break;
    case 'closing':
        $sql .= " ORDER BY (p.target_signatures - (SELECT COUNT(*) FROM signatures s WHERE s.petition_id = p.id)) / p.target_signatures ASC";
        break;
    default: // newest
        $sql .= " ORDER BY p.created_at DESC";
}

// Dodaj limit i offset
$sql .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Pobierz petycje
$petitions = $db->query($sql, $params)->fetchAll();

// Pobierz kategorie dla filtra
$categories = $db->query(
    "SELECT DISTINCT category FROM petitions WHERE status = 'active' AND category IS NOT NULL"
)->fetchAll(PDO::FETCH_COLUMN);

// Sprawdź, które petycje użytkownik już podpisał
$signed_petitions = [];
if (isset($_SESSION['user_id'])) {
    $signed = $db->query(
        "SELECT petition_id FROM signatures WHERE user_id = ?",
        [$_SESSION['user_id']]
    )->fetchAll();
    $signed_petitions = array_column($signed, 'petition_id');
}

$page_title = "Petycje - BreathTime";
ob_start();
?>

<div class="content-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Aktywne Petycje</h1>
        <?php 
        if (isset($_SESSION['user_id'])) {
            $user = $db->query(
                "SELECT is_admin FROM users WHERE id = ?", 
                [$_SESSION['user_id']]
            )->fetch();
            
            if ($user && $user['is_admin']) {
                echo '<a href="create-petition.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Utwórz nową petycję
                </a>';
            }
        }
        ?>
    </div>

    <div class="filters-bar">
        <div class="search-box">
            <input type="text" 
                   placeholder="Szukaj petycji..." 
                   value="<?php echo htmlspecialchars($search); ?>"
                   onchange="updateFilters('search', this.value)"
                   class="form-control">
        </div>
        
        <div class="filter-group">
            <select class="form-control" onchange="updateFilters('category', this.value)">
                <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>Wszystkie kategorie</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                            <?php echo $category === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select class="form-control" onchange="updateFilters('sort', this.value)">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Najnowsze</option>
                <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Najpopularniejsze</option>
                <option value="closing" <?php echo $sort === 'closing' ? 'selected' : ''; ?>>Blisko celu</option>
            </select>
        </div>
    </div>

    <?php if ($petitions): ?>
        <div class="petitions-grid">
            <?php foreach ($petitions as $petition): ?>
                <div class="petition-card">
                    <div class="petition-header">
                        <span class="status-badge">
                            <?php echo htmlspecialchars($petition['category']); ?>
                        </span>
                        <h2>
                            <a href="view-petition.php?id=<?php echo $petition['id']; ?>">
                                <?php echo htmlspecialchars($petition['title']); ?>
                            </a>
                        </h2>
                        <p class="petition-author">
                            Autor: <?php echo htmlspecialchars($petition['creator_name']); ?>
                        </p>
                    </div>
                    
                    <div class="petition-progress">
                        <?php
                        $progress = ($petition['signatures_count'] / $petition['target_signatures']) * 100;
                        $progress = min(100, $progress);
                        ?>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <div class="progress-stats">
                            <span><?php echo number_format($petition['signatures_count']); ?> podpisów</span>
                            <span><?php echo number_format($petition['target_signatures']); ?> cel</span>
                        </div>
                    </div>

                    <div class="petition-actions">
                        <?php if (in_array($petition['id'], $signed_petitions)): ?>
                            <button class="btn btn-success btn-sm" disabled>
                                <i class="fas fa-check"></i> Podpisano
                            </button>
                        <?php else: ?>
                            <a href="sign-petition.php?id=<?php echo $petition['id']; ?>" 
                               class="btn btn-primary btn-sm">
                                Podpisz petycję
                            </a>
                        <?php endif; ?>
                        <a href="view-petition.php?id=<?php echo $petition['id']; ?>" 
                           class="btn btn-info btn-sm">
                            <i class="fas fa-info-circle"></i> Szczegóły
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&sort=<?php echo $sort; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>" 
                       class="btn btn-sm <?php echo $page === $i ? 'btn-primary' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            Nie znaleziono żadnych petycji spełniających kryteria wyszukiwania.
        </div>
    <?php endif; ?>
</div>

<style>
.filters-bar {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 200px;
    position: relative;
}

.filter-group {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.petitions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.petition-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1.5rem;
    transition: transform 0.3s ease;
}

.petition-card:hover {
    transform: translateY(-5px);
}

.petition-header {
    margin-bottom: 1rem;
}

.petition-header h2 {
    font-size: 1.25rem;
    margin: 0.5rem 0;
}

.petition-header h2 a {
    color: var(--text-color);
    text-decoration: none;
}

.petition-header h2 a:hover {
    color: var(--accent);
}

.petition-author {
    color: var(--text-light);
    font-size: 0.9rem;
}

.petition-progress {
    margin: 1rem 0;
}

.progress-bar {
    height: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.25rem;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-bar .progress {
    height: 100%;
    background: var(--accent);
    border-radius: 0.25rem;
    transition: width 0.3s ease;
}

.progress-stats {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    color: var(--text-light);
}

.petition-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
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

@media (max-width: 768px) {
    .filters-bar {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
        flex-direction: column;
    }
    
    .filter-group select {
        width: 100%;
    }
}
</style>

<script>
function updateFilters(type, value) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set(type, value);
    urlParams.set('page', '1'); // Reset to first page
    window.location.href = '?' + urlParams.toString();
}
</script>

<?php
$content = ob_get_clean();
require 'includes/tech_layout.php';
?>
