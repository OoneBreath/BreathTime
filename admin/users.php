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
            (SELECT COUNT(*) FROM signatures s WHERE s.user_id = u.id) as signatures_count,
            (SELECT COUNT(*) FROM petition_proposals pp WHERE pp.user_id = u.id) as petitions_count,
            (SELECT GROUP_CONCAT(DISTINCT p.title SEPARATOR ', ') 
             FROM petition_proposals pp 
             JOIN petitions p ON pp.petition_id = p.id 
             WHERE pp.user_id = u.id 
             LIMIT 3) as petition_titles,
            (SELECT GROUP_CONCAT(DISTINCT p.title SEPARATOR ', ') 
             FROM signatures s 
             JOIN petitions p ON s.petition_id = p.id 
             WHERE s.user_id = u.id 
             LIMIT 3) as signed_petition_titles
     FROM users u 
     ORDER BY u.created_at DESC"
)->fetchAll();

// Funkcja do konwersji kodu kraju na flagę emoji
function getCountryFlag($countryCode) {
    if (empty($countryCode)) return '';
    // Konwersja kodu kraju na emoji flagi
    $flag = mb_convert_encoding('&#' . (127397 + ord(substr($countryCode, 0, 1))) . ';', 'UTF-8', 'HTML-ENTITIES') .
            mb_convert_encoding('&#' . (127397 + ord(substr($countryCode, 1, 1))) . ';', 'UTF-8', 'HTML-ENTITIES');
    return $flag;
}

$page_title = 'Zarządzanie użytkownikami - Panel admina';
ob_start(); 
?>

<!-- Dodaj bibliotekę flag -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/6.6.6/css/flag-icons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@6.11.0/css/flag-icons.min.css">

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
                    <th>Kraj</th>
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
                                <?php echo $user['status'] === 'active' ? 'Aktywny' : ($user['status'] === 'suspended' ? 'Zawieszony' : 'Niezweryfikowany'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['country_code']): ?>
                                <span title="<?php echo htmlspecialchars($user['country_name']); ?>" class="country-info">
                                    <span class="fi fi-<?php echo strtolower($user['country_code']); ?>"></span>
                                    <?php echo htmlspecialchars($user['country_name']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Brak danych</span>
                            <?php endif; ?>
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
                                                'role_description' => $user['role_description'],
                                                'petition_titles' => $user['petition_titles'],
                                                'signed_petition_titles' => $user['signed_petition_titles']
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
                        <td colspan="7">
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
                                                <i class="fas fa-map-marker-alt" title="IP rejestracji"></i>
                                                <strong>IP rejestracji:</strong>
                                                <span class="details-registration-ip"></span>
                                            </div>
                                            <div class="mb-2">
                                                <i class="fas fa-sign-in-alt" title="Ostatnie IP logowania"></i>
                                                <strong>Ostatnie IP:</strong>
                                                <span class="details-last-login"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="details-section">
                                            <div class="mb-2">
                                                <i class="fas fa-file-signature" title="Podpisane petycje"></i>
                                                <strong>Podpisane petycje (<?php echo $user['signatures_count']; ?>):</strong>
                                                <span class="details-signatures"></span>
                                            </div>
                                            <div class="mb-2">
                                                <i class="fas fa-file-alt" title="Dodane petycje"></i>
                                                <strong>Dodane petycje (<?php echo $user['petitions_count']; ?>):</strong>
                                                <span class="details-petitions"></span>
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
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #ffffff;
    background: rgba(255, 255, 255, 0.15);
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
}

.country-info .fi {
    font-size: 1.2em;
    border-radius: 2px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.text-muted {
    color: rgba(255, 255, 255, 0.6) !important;
}

.user-details {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    margin: 10px;
}

.user-details i {
    width: 20px;
    margin-right: 8px;
    color: #a8b6d0;
}

.user-details strong {
    color: #a8b6d0;
    margin-right: 8px;
}

.user-details span {
    color: #ffffff;
}

.user-avatar {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
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
    const detailsContent = detailsRow.querySelector('.user-details');

    // Aktualizacja szczegółów
    detailsContent.querySelector('.details-email').textContent = userData.email;
    if (userData.phone) {
        detailsContent.querySelector('.details-phone').textContent = userData.phone;
        detailsContent.querySelector('.details-phone-container').style.display = 'block';
    } else {
        detailsContent.querySelector('.details-phone-container').style.display = 'none';
    }
    
    // IP adresy
    detailsContent.querySelector('.details-registration-ip').textContent = userData.registration_ip || 'Brak danych';
    detailsContent.querySelector('.details-last-login').textContent = userData.last_login || 'Brak danych';
    
    // Petycje
    detailsContent.querySelector('.details-signatures').textContent = 
        userData.signed_petition_titles || 'Brak podpisanych petycji';
    detailsContent.querySelector('.details-petitions').textContent = 
        userData.petition_titles || 'Brak dodanych petycji';

    // Przełączanie widoczności
    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = 'table-row';
        button.querySelector('i').classList.remove('fa-info-circle');
        button.querySelector('i').classList.add('fa-times-circle');
        button.title = 'Zamknij szczegóły';
    } else {
        detailsRow.style.display = 'none';
        button.querySelector('i').classList.remove('fa-times-circle');
        button.querySelector('i').classList.add('fa-info-circle');
        button.title = 'Szczegóły użytkownika';
    }
}
</script>

<?php
$content = ob_get_clean();
require_once 'tech_layout.php';
?>
