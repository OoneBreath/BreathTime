<?php
require_once 'includes/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

// Oznacz wszystkie powiadomienia jako przeczytane
if (isset($_POST['mark_all_read'])) {
    $db->query(
        "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL",
        [$_SESSION['user_id']]
    );
}

// Pobierz powiadomienia użytkownika
$notifications = $db->query(
    "SELECT * FROM notifications 
     WHERE user_id = ? 
     ORDER BY created_at DESC 
     LIMIT 50",
    [$_SESSION['user_id']]
)->fetchAll();

$page_title = 'Powiadomienia - BreathTime';
ob_start(); ?>

<div class="content-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Powiadomienia</h1>
        <?php if (!empty($notifications)): ?>
            <form method="POST" action="">
                <button type="submit" name="mark_all_read" class="btn btn-secondary">
                    <i class="fas fa-check-double"></i> Oznacz wszystkie jako przeczytane
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">
            Nie masz żadnych powiadomień.
        </div>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['read_at'] ? '' : 'unread'; ?>">
                    <div class="notification-icon">
                        <img src="/images/breathtime-logo-negative-256px.png" alt="BreathTime" class="notification-logo">
                        <?php
                        switch ($notification['type']) {
                            case 'petition_signed':
                                echo '<i class="fas fa-signature"></i>';
                                break;
                            case 'petition_comment':
                                echo '<i class="fas fa-comment"></i>';
                                break;
                            case 'petition_status':
                                echo '<i class="fas fa-info-circle"></i>';
                                break;
                            case 'system':
                                echo '<i class="fas fa-bell"></i>';
                                break;
                            default:
                                echo '<i class="fas fa-circle"></i>';
                        }
                        ?>
                    </div>
                    
                    <div class="notification-content">
                        <div class="notification-message">
                            <?php echo htmlspecialchars($notification['message']); ?>
                            <?php if ($notification['link']): ?>
                                <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="notification-link">
                                    Zobacz więcej
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="notification-meta">
                            <span class="notification-time">
                                <?php
                                $date = new DateTime($notification['created_at']);
                                $now = new DateTime();
                                $diff = $now->diff($date);
                                
                                if ($diff->y > 0) {
                                    echo $diff->y . ' ' . ($diff->y == 1 ? 'rok' : ($diff->y < 5 ? 'lata' : 'lat')) . ' temu';
                                } elseif ($diff->m > 0) {
                                    echo $diff->m . ' ' . ($diff->m == 1 ? 'miesiąc' : ($diff->m < 5 ? 'miesiące' : 'miesięcy')) . ' temu';
                                } elseif ($diff->d > 0) {
                                    echo $diff->d . ' ' . ($diff->d == 1 ? 'dzień' : 'dni') . ' temu';
                                } elseif ($diff->h > 0) {
                                    echo $diff->h . ' ' . ($diff->h == 1 ? 'godzina' : ($diff->h < 5 ? 'godziny' : 'godzin')) . ' temu';
                                } elseif ($diff->i > 0) {
                                    echo $diff->i . ' ' . ($diff->i == 1 ? 'minuta' : ($diff->i < 5 ? 'minuty' : 'minut')) . ' temu';
                                } else {
                                    echo 'przed chwilą';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.notification-logo {
    width: 30px;
    height: auto;
    margin-right: 10px;
    filter: brightness(0) invert(1);
}

.notification-icon {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: var(--secondary-bg);
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.notification-item.unread {
    background: var(--content-bg);
    border-left: 4px solid var(--accent);
}

.notification-item:hover {
    background: var(--bg-hover);
}

.notification-content {
    flex: 1;
}

.notification-message {
    margin-bottom: 0.5rem;
    line-height: 1.5;
}

.notification-link {
    color: var(--primary-color);
    text-decoration: none;
    margin-left: 0.5rem;
}

.notification-link:hover {
    text-decoration: underline;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--text-light);
    font-size: 0.9rem;
}

.notification-time {
    color: var(--text-light);
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

.mb-4 {
    margin-bottom: 1.5rem;
}
</style>

<?php
$content = ob_get_clean();
require 'includes/tech_layout.php';
?>
