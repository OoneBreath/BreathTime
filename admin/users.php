<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/mail.php';

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
        $userId = $_POST['user_id'] ?? null;
        
        if ($userId) {
            switch ($_POST['action']) {
                case 'delete':
                    try {
                        // Najpierw usuń powiązane rekordy
                        $db->query("DELETE FROM petition_proposals WHERE user_id = ?", [$userId]);
                        $db->query(
                            "DELETE FROM subscribers WHERE email = (
                                SELECT email COLLATE utf8mb4_unicode_ci 
                                FROM users WHERE id = ?
                            )", 
                            [$userId]
                        );
                        $db->query("DELETE FROM notifications WHERE user_id = ?", [$userId]);
                        $db->query("DELETE FROM signatures WHERE user_id = ?", [$userId]);
                        
                        // Teraz usuń użytkownika
                        $db->query("DELETE FROM users WHERE id = ?", [$userId]);
                        
                        $_SESSION['success'] = "Użytkownik został usunięty.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas usuwania użytkownika: " . $e->getMessage();
                    }
                    break;
                    
                case 'make_admin':
                    try {
                        $db->query(
                            "UPDATE users SET is_admin = TRUE WHERE id = ?",
                            [$userId]
                        );
                        $_SESSION['success'] = "Użytkownik został awansowany na administratora.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas zmiany uprawnień użytkownika: " . $e->getMessage();
                    }
                    break;
                    
                case 'remove_admin':
                    try {
                        $db->query(
                            "UPDATE users SET is_admin = FALSE WHERE id = ?",
                            [$userId]
                        );
                        $_SESSION['success'] = "Użytkownik został zdegradowany do zwykłego użytkownika.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas zmiany uprawnień użytkownika: " . $e->getMessage();
                    }
                    break;
                    
                case 'verify':
                    try {
                        $db->query(
                            "UPDATE users SET status = 'active', verification_token = NULL WHERE id = ?",
                            [$userId]
                        );
                        $_SESSION['success'] = "Konto użytkownika zostało zweryfikowane.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas weryfikacji konta: " . $e->getMessage();
                    }
                    break;
                    
                case 'suspend':
                    try {
                        $db->query(
                            "UPDATE users SET status = 'suspended' WHERE id = ?",
                            [$userId]
                        );
                        $_SESSION['success'] = "Konto użytkownika zostało zawieszone.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas zawieszania konta: " . $e->getMessage();
                    }
                    break;
                    
                case 'activate':
                    try {
                        $db->query(
                            "UPDATE users SET status = 'active' WHERE id = ?",
                            [$userId]
                        );
                        $_SESSION['success'] = "Konto użytkownika zostało aktywowane.";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas aktywacji konta: " . $e->getMessage();
                    }
                    break;
                    
                case 'verify_role':
                    try {
                        // Pobierz aktualną rolę użytkownika
                        $stmt = $db->query("SELECT requested_role, email, name FROM users WHERE id = ?", [$userId]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user && $user['requested_role']) {
                            // Zatwierdź rolę
                            $db->query(
                                "UPDATE users SET 
                                    role = requested_role,
                                    requested_role = NULL,
                                    role_verified = TRUE, 
                                    role_verification_date = NOW() 
                                WHERE id = ?",
                                [$userId]
                            );
                            
                            $roleName = $roleNames[$user['requested_role']] ?? $user['requested_role'];
                            
                            // Wyślij email z potwierdzeniem
                            $subject = "Twoja rola została zatwierdzona - BreathTime";
                            $message = "
                                <p>Witaj <strong>{$user['name']}</strong>,</p>
                                <div class='highlight'>
                                    <p>Z przyjemnością informujemy, że Twoja prośba o rolę <strong>{$roleName}</strong> została zatwierdzona!</p>
                                </div>
                                <p>Od teraz możesz korzystać z wszystkich funkcji dostępnych dla tej roli.</p>
                                <a href='https://breathtime.info/dashboard.php' class='button'>Przejdź do panelu</a>
                                <p style='margin-top: 20px;'>Pozdrawiamy,<br>Zespół BreathTime</p>";
                            
                            sendEmail($user['email'], $subject, $message);
                            $_SESSION['success'] = "Rola użytkownika została zatwierdzona. Email z potwierdzeniem został wysłany.";
                        }
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas zatwierdzania roli: " . $e->getMessage();
                    }
                    break;
                    
                case 'reject_role':
                    try {
                        // Pobierz dane użytkownika przed odrzuceniem roli
                        $stmt = $db->query("SELECT email, name, requested_role FROM users WHERE id = ?", [$userId]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user && $user['requested_role']) {
                            $roleName = $roleNames[$user['requested_role']] ?? $user['requested_role'];
                            
                            // Odrzuć rolę
                            $db->query(
                                "UPDATE users SET 
                                    requested_role = NULL,
                                    role_verified = FALSE, 
                                    role_verification_date = NOW() 
                                WHERE id = ?",
                                [$userId]
                            );
                            
                            // Wyślij email z informacją o odrzuceniu
                            $subject = "Aktualizacja statusu roli - BreathTime";
                            $message = "
                                <p>Witaj <strong>{$user['name']}</strong>,</p>
                                <div class='highlight'>
                                    <p>Niestety, Twoja prośba o rolę <strong>{$roleName}</strong> została odrzucona.</p>
                                </div>
                                <p>Jeśli masz pytania lub chciałbyś złożyć nową prośbę z dodatkowym uzasadnieniem, 
                                   skontaktuj się z nami odpowiadając na tego emaila.</p>
                                <p style='margin-top: 20px;'>Pozdrawiamy,<br>Zespół BreathTime</p>";
                            
                            sendEmail($user['email'], $subject, $message);
                            $_SESSION['success'] = "Rola użytkownika została odrzucona. Email z informacją został wysłany.";
                        }
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas odrzucania roli: " . $e->getMessage();
                    }
                    break;

                case 'reset_role':
                    try {
                        // Pobierz dane użytkownika przed resetowaniem roli
                        $stmt = $db->query("SELECT email, name, role FROM users WHERE id = ?", [$userId]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user) {
                            $oldRoleName = $roleNames[$user['role']] ?? $user['role'];
                            
                            // Resetuj rolę do 'user'
                            $db->query(
                                "UPDATE users SET 
                                    role = 'user',
                                    role_verified = TRUE,
                                    role_verification_date = NOW(),
                                    requested_role = NULL
                                WHERE id = ?",
                                [$userId]
                            );
                            
                            // Wyślij email z informacją o zmianie
                            $subject = "Zmiana roli w systemie - BreathTime";
                            $message = "
                                <p>Witaj <strong>{$user['name']}</strong>,</p>
                                <div class='highlight'>
                                    <p>Informujemy, że Twoja rola w systemie została zresetowana do poziomu podstawowego użytkownika.</p>
                                </div>
                                <p>Jeśli masz pytania, skontaktuj się z nami odpowiadając na tego emaila.</p>
                                <p style='margin-top: 20px;'>Pozdrawiamy,<br>Zespół BreathTime</p>";
                            
                            sendEmail($user['email'], $subject, $message);
                            $_SESSION['success'] = "Rola użytkownika została zresetowana do podstawowej. Email z informacją został wysłany.";
                        }
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas resetowania roli: " . $e->getMessage();
                    }
                    break;
                    
                case 'change_role':
                    try {
                        $newRole = $_POST['new_role'] ?? null;
                        
                        if ($newRole) {
                            $db->query(
                                "UPDATE users SET 
                                    role = ?,
                                    role_verified = TRUE,
                                    role_verification_date = NOW(),
                                    requested_role = NULL
                                WHERE id = ?",
                                [$newRole, $userId]
                            );
                            $_SESSION['success'] = "Rola użytkownika została zmieniona.";
                        }
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Błąd podczas zmiany roli: " . $e->getMessage();
                    }
                    break;
            }
        }
    }
    
    // Przekieruj, aby uniknąć ponownego wysłania formularza
    header('Location: users.php');
    exit();
}

// Pobierz listę użytkowników
$users = $db->query(
    "SELECT u.*, 
            (SELECT COUNT(*) FROM signatures WHERE user_id = u.id) as signatures_count,
            (SELECT COUNT(*) FROM petitions WHERE created_by = u.id) as petitions_count,
            CASE 
                WHEN u.status = 'active' THEN 'Aktywny'
                WHEN u.status = 'unverified' THEN 'Niezweryfikowany'
                WHEN u.status = 'suspended' THEN 'Zawieszony'
                ELSE u.status
            END as status_pl
     FROM users u 
     ORDER BY u.created_at DESC"
)->fetchAll();

$page_title = 'Zarządzanie użytkownikami - Panel admina';
ob_start(); 
?>

<!-- Dodaj bibliotekę flag -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/6.6.6/css/flag-icons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">

<?php
// Definicje ról i ich tłumaczenia
$roleNames = [
    'volunteer' => 'Wolontariusz',
    'educator' => 'Edukator',
    'monitor' => 'Monitor',
    'foundation' => 'Fundacja',
    'tech_partner' => 'Partner Technologiczny',
    'edu_institution' => 'Instytucja Edukacyjna',
    'expert' => 'Ekspert',
    'strategic_partner' => 'Partner Strategiczny',
    'ambassador' => 'Ambasador',
    'admin' => 'Administrator',
    'user' => 'Użytkownik'
];

// Kolory dla ról - stałe kolory dla każdej roli
$roleColors = [
    'volunteer' => '#2ecc71',      // Zielony
    'educator' => '#3498db',       // Niebieski
    'monitor' => '#9b59b6',        // Fioletowy
    'foundation' => '#f1c40f',     // Żółty
    'tech_partner' => '#1abc9c',   // Turkusowy
    'edu_institution' => '#34495e', // Granatowy
    'expert' => '#e74c3c',         // Czerwony
    'strategic_partner' => '#d35400', // Pomarańczowy
    'ambassador' => '#16a085',     // Morski
    'admin' => '#8e44ad',          // Fioletowy
    'user' => '#7f8c8d'           // Szary
];
?>

<div class="content-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Zarządzanie użytkownikami</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Wróć do panelu
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
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Avatar</th>
                    <th>ID</th>
                    <th>Podstawowe informacje</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="<?php echo ($user['requested_role'] && !$user['role_verified']) ? 'role-pending' : ''; ?>">
                        <td>
                            <div class="user-avatar">
                                <?php if ($user['avatar']): ?>
                                    <img src="../uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                         alt="Avatar" 
                                         class="avatar-img">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-2x"></i>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="user-id"><?php echo htmlspecialchars($user['id']); ?></td>
                        <td>
                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="role-section">
                                <div class="current-role" style="background-color: <?php echo $roleColors[$user['role']] ?? '#7f8c8d'; ?>">
                                    <?php echo $roleNames[$user['role']] ?? 'Użytkownik'; ?>
                                </div>
                                
                                <?php if ($user['requested_role'] && !$user['role_verified']): ?>
                                    <div class="requested-role-badge">
                                        <span class="role-label" style="color: <?php echo $roleColors[$user['requested_role']] ?? '#7f8c8d'; ?>">
                                            Oczekuje: <?php echo $roleNames[$user['requested_role']] ?? $user['requested_role']; ?>
                                        </span>
                                        <div class="role-actions">
                                            <button type="button" class="action-btn approve" 
                                                    onclick="confirmAction('verify_role', <?php echo $user['id']; ?>, '<?php echo $user['requested_role']; ?>')"
                                                    title="Zatwierdź rolę">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="action-btn reject" 
                                                    onclick="confirmAction('reject_role', <?php echo $user['id']; ?>, '<?php echo $user['requested_role']; ?>')"
                                                    title="Odrzuć rolę">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!$user['is_admin']): ?>
                                    <button type="button" class="action-btn promote" 
                                            onclick="confirmAction('make_admin', <?php echo $user['id']; ?>)"
                                            title="Nadaj uprawnienia administratora">
                                        <i class="fas fa-user-shield"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="action-btn demote" 
                                            onclick="confirmAction('remove_admin', <?php echo $user['id']; ?>)"
                                            title="Odbierz uprawnienia administratora">
                                        <i class="fas fa-user-times"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if ($user['role'] !== 'user'): ?>
                                    <button type="button" class="action-btn reset" 
                                            onclick="confirmAction('reset_role', <?php echo $user['id']; ?>)"
                                            title="Zresetuj do roli użytkownika">
                                        <i class="fas fa-user-minus"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $user['status'] === 'active' ? 'active' : ($user['status'] === 'suspended' ? 'suspended' : 'unverified'); ?>">
                                <?php echo $user['status_pl']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-secondary btn-sm" 
                                        onclick="toggleDetails(this, '<?php 
                                            echo htmlspecialchars(json_encode([
                                                'email' => $user['email'],
                                                'phone' => $user['phone'],
                                                'created_at' => $user['created_at'],
                                                'last_login' => $user['last_login_ip'],
                                                'registration_ip' => $user['registration_ip'],
                                                'role_description' => $user['role_description']
                                            ])); 
                                        ?>')" title="Szczegóły użytkownika">
                                    <i class="fas fa-info-circle"></i>
                                </button>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Usuń użytkownika"
                                            onclick="return confirm('Czy na pewno chcesz usunąć tego użytkownika? Ta operacja jest nieodwracalna!')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr class="details-row" style="display: none;">
                        <td colspan="6">
                            <div class="user-details p-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="details-section">
                                            <div class="mb-2">
                                                <i class="fas fa-envelope" title="Adres email"></i>
                                                <strong>Email:</strong> 
                                                <span class="details-email"></span>
                                            </div>
                                            <div class="mb-2 details-phone-container" style="display: none;">
                                                <i class="fas fa-phone" title="Numer telefonu"></i>
                                                <strong>Telefon:</strong> 
                                                <span class="details-phone"></span>
                                            </div>
                                            <div class="mb-2">
                                                <i class="fas fa-calendar" title="Data dołączenia"></i>
                                                <strong>Data dołączenia:</strong> 
                                                <span class="details-joined"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="details-section">
                                            <div class="mb-2 details-ip-container">
                                                <i class="fas fa-network-wired" title="Adres IP"></i>
                                                <strong>IP:</strong> 
                                                <span class="details-ip"></span>
                                            </div>
                                            <div class="mb-2">
                                                <i class="fas fa-file-signature" title="Liczba utworzonych petycji"></i>
                                                <strong>Petycje:</strong> 
                                                <span class="details-petitions"></span>
                                            </div>
                                            <div class="mb-2">
                                                <i class="fas fa-signature" title="Liczba podpisanych petycji"></i>
                                                <strong>Podpisy:</strong> 
                                                <span class="details-signatures"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.table {
    --bs-table-bg: rgba(255, 255, 255, 0.05);
    --bs-table-color: #e1e1e1;
    border: 1px solid var(--border);
    border-radius: 0.5rem;
}

.table th {
    color: #ffffff;
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 2px solid var(--border);
    padding: 1rem;
    font-weight: 600;
}

.table td {
    border-color: var(--border);
    padding: 1rem;
    vertical-align: middle;
}

.user-id {
    color: #ffd700 !important;
    font-weight: 500;
    font-size: 1.1em;
}

.user-name {
    color: #ffffff !important;
    font-size: 1.1em;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.country-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.country-name {
    color: #8e9cc0;
}

.flag-icon {
    margin-right: 8px;
    border-radius: 2px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.user-details {
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid var(--border);
    color: #e1e1e1;
}

.details-section {
    background: rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.user-details i {
    width: 20px;
    margin-right: 8px;
    color: #8e9cc0;
}

.user-details strong {
    color: #8e9cc0;
    margin-right: 8px;
    font-weight: 500;
}

.user-details span:not(.flag-icon) {
    color: #ffffff;
}

.badge {
    font-size: 0.85em;
    padding: 0.4em 0.8em;
    border-radius: 12px;
}

.role-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    color: white;
    font-size: 0.85em;
    margin-top: 4px;
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.requested-role-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    background-color: rgba(255,255,255,0.1);
    color: #8e9cc0;
    font-size: 0.85em;
    margin-top: 4px;
    font-style: italic;
}

.role-pending {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--secondary-bg);
    border: 2px solid var(--border);
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.details-row td {
    padding: 0 !important;
}

.text-muted {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #8e9cc0 !important;
}

/* Style dla tooltipów */
[title] {
    position: relative;
    cursor: help;
}

.btn[title] {
    cursor: pointer;
}

/* Poprawka dla buttonów z ikonami */
.btn-group .btn {
    position: relative;
    transition: all 0.2s ease-in-out;
}

.btn-group .btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* Zwiększenie widoczności ikon w szczegółach */
.user-details i {
    width: 20px;
    margin-right: 8px;
    color: #8e9cc0;
    transition: color 0.2s ease-in-out;
}

.user-details i:hover {
    color: #ffffff;
}

.iti__flag {
    width: 20px;
    height: 15px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/img/flags.png");
    background-repeat: no-repeat;
    margin-right: 8px;
    object-fit: cover;
}

.country-info {
    display: flex;
    align-items: center;
    margin-top: 0.5rem;
}

.country-name {
    color: #8e9cc0;
    font-size: 0.9em;
}

.btn-group {
    display: flex;
    gap: 4px;
}

.btn-group form {
    margin: 0;
}

.btn-group .btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.btn-group .btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.btn-success {
    background-color: #4CAF50;
    border-color: #43A047;
}

.btn-warning {
    background-color: #FF9800;
    border-color: #F57C00;
}

.btn-info {
    background-color: #2196F3;
    border-color: #1E88E5;
}

.btn-danger {
    background-color: #F44336;
    border-color: #E53935;
}

.role-section {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}

.role-label {
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: 500;
    color: white;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.current-role {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 4px;
    color: white;
    font-size: 0.9em;
    font-weight: 500;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.requested-role-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 4px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    gap: 8px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    padding: 0;
    border: none;
    border-radius: 4px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
}

.action-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.action-btn.approve {
    background: linear-gradient(135deg, rgba(46, 204, 113, 0.2), rgba(46, 204, 113, 0.3));
    border-color: rgba(46, 204, 113, 0.4);
    color: #2ecc71;
}

.action-btn.reject {
    background: linear-gradient(135deg, rgba(231, 76, 60, 0.2), rgba(231, 76, 60, 0.3));
    border-color: rgba(231, 76, 60, 0.4);
    color: #e74c3c;
}

.action-btn.promote {
    background: linear-gradient(135deg, rgba(142, 68, 173, 0.2), rgba(142, 68, 173, 0.3));
    border-color: rgba(142, 68, 173, 0.4);
    color: #8e44ad;
}

.action-btn.demote {
    background: linear-gradient(135deg, rgba(211, 84, 0, 0.2), rgba(211, 84, 0, 0.3));
    border-color: rgba(211, 84, 0, 0.4);
    color: #d35400;
}

.action-btn.reset {
    background: linear-gradient(135deg, rgba(127, 140, 141, 0.2), rgba(127, 140, 141, 0.3));
    border-color: rgba(127, 140, 141, 0.4);
    color: #7f8c8d;
}

.action-btn i {
    font-size: 1.1rem;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.role-actions {
    display: inline-flex;
    gap: 6px;
}

.role-section {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.role-label {
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Dodajemy hover efekt dla wszystkich przycisków w tabeli */
.table .btn {
    border-radius: 4px;
    transition: all 0.2s ease;
}

.table .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
}

.table .btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>

<script>
function confirmAction(action, userId, role = '') {
    let message = '';
    switch(action) {
        case 'verify_role':
            message = `Czy na pewno chcesz zatwierdzić rolę "${roleNames[role] || role}" dla tego użytkownika?`;
            break;
        case 'reject_role':
            message = `Czy na pewno chcesz odrzucić rolę "${roleNames[role] || role}" dla tego użytkownika?`;
            break;
        case 'make_admin':
            message = 'Czy na pewno chcesz nadać uprawnienia administratora?';
            break;
        case 'remove_admin':
            message = 'Czy na pewno chcesz odebrać uprawnienia administratora?';
            break;
        case 'reset_role':
            message = 'Czy na pewno chcesz zresetować rolę do zwykłego użytkownika?';
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

        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = userId;

        form.appendChild(actionInput);
        form.appendChild(userIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Dodaj zmienną z tłumaczeniami ról do JavaScript
const roleNames = <?php echo json_encode($roleNames); ?>;
</script>

<script>
function toggleDetails(button, userDataJson) {
    const userData = JSON.parse(userDataJson);
    const row = button.closest('tr');
    const detailsRow = row.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (detailsRow.style.display === 'none') {
        // Pokaż szczegóły
        detailsRow.style.display = 'table-row';
        icon.classList.remove('fa-plus');
        icon.classList.add('fa-minus');
        
        // Wypełnij dane
        detailsRow.querySelector('.details-email').textContent = userData.email;
        
        if (userData.phone) {
            detailsRow.querySelector('.details-phone-container').style.display = 'block';
            detailsRow.querySelector('.details-phone').textContent = userData.phone;
        }
        
        if (userData.ip_address) {
            detailsRow.querySelector('.details-ip').textContent = userData.ip_address;
        } else {
            detailsRow.querySelector('.details-ip').textContent = 'Brak danych';
        }
        
        detailsRow.querySelector('.details-petitions').textContent = userData.petitions_count;
        detailsRow.querySelector('.details-signatures').textContent = userData.signatures_count;
    } else {
        // Ukryj szczegóły
        detailsRow.style.display = 'none';
        icon.classList.remove('fa-minus');
        icon.classList.add('fa-plus');
    }
}

function showDescription(description) {
    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '1050';
    
    modal.innerHTML = `
        <div style="
            background: #1a1f2d;
            border-radius: 8px;
            padding: 20px;
            max-width: 500px;
            width: 90%;
            position: relative;
            box-shadow: 0 3px 20px rgba(0, 0, 0, 0.5);
        ">
            <div style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                border-bottom: 1px solid #2d3546;
                padding-bottom: 10px;
            ">
                <h5 style="
                    margin: 0;
                    color: #ffffff;
                    font-size: 1.25rem;
                ">Opis roli</h5>
                <button style="
                    background: none;
                    border: none;
                    color: #8e9cc0;
                    font-size: 1.5rem;
                    cursor: pointer;
                    padding: 5px;
                " onclick="this.closest('div[style]').parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="
                background: #232836;
                border: 1px solid #2d3546;
                border-radius: 4px;
                padding: 15px;
                color: #e1e1e1;
                white-space: pre-wrap;
                line-height: 1.5;
            ">${description}</div>
        </div>
    `;
    
    // Zamykanie po kliknięciu w tło
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    document.body.appendChild(modal);
}

// Inicjalizacja tooltipów Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            placement: 'top',
            trigger: 'hover'
        });
    });
});
</script>

<?php
$content = ob_get_clean();
require_once 'tech_layout.php';
?>
