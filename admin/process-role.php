<?php
require_once '../includes/config.php';
session_start();

// Sprawdź czy użytkownik jest zalogowany i jest adminem
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if (!$userId || !$action) {
        $_SESSION['error'] = 'Brakujące dane.';
        header('Location: users.php');
        exit();
    }
    
    $db = Database::getInstance();
    
    try {
        switch ($action) {
            case 'approve':
                // Zatwierdź rolę
                $db->query(
                    "UPDATE users 
                    SET role = requested_role,
                        role_verified = TRUE, 
                        role_verification_date = NOW() 
                    WHERE id = ?",
                    [$userId]
                );
                
                // Pobierz dane użytkownika
                $user = $db->query(
                    "SELECT email, role FROM users WHERE id = ?",
                    [$userId]
                )->fetch();
                
                // Wyślij powiadomienie do użytkownika
                $db->query(
                    "INSERT INTO notifications (user_id, type, message, created_at) 
                    VALUES (?, 'role_approved', ?, NOW())",
                    [
                        $userId,
                        "Twoja rola została zatwierdzona przez administratora."
                    ]
                );
                
                $_SESSION['success'] = 'Rola została zatwierdzona.';
                break;
                
            case 'reject':
                // Odrzuć rolę
                $db->query(
                    "UPDATE users 
                    SET role = NULL, 
                        role_verified = FALSE, 
                        role_verification_date = NULL, 
                        role_application_date = NULL 
                    WHERE id = ?",
                    [$userId]
                );
                
                // Wyślij powiadomienie do użytkownika
                $db->query(
                    "INSERT INTO notifications (user_id, type, message, created_at) 
                    VALUES (?, 'role_rejected', ?, NOW())",
                    [
                        $userId,
                        "Twój wniosek o rolę został odrzucony przez administratora."
                    ]
                );
                
                $_SESSION['success'] = 'Wniosek o rolę został odrzucony.';
                break;
                
            case 'remove':
                // Usuń rolę
                $db->query(
                    "UPDATE users 
                    SET role = NULL, 
                        role_verified = FALSE, 
                        role_verification_date = NULL, 
                        role_application_date = NULL 
                    WHERE id = ?",
                    [$userId]
                );
                
                // Wyślij powiadomienie do użytkownika
                $db->query(
                    "INSERT INTO notifications (user_id, type, message, created_at) 
                    VALUES (?, 'role_removed', ?, NOW())",
                    [
                        $userId,
                        "Twoja rola została usunięta przez administratora."
                    ]
                );
                
                $_SESSION['success'] = 'Rola została usunięta.';
                break;
                
            default:
                throw new Exception('Nieznana akcja.');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Wystąpił błąd: ' . $e->getMessage();
    }
    
    header('Location: users.php');
    exit();
}

// Jeśli nie POST, przekieruj
header('Location: users.php');
exit();
?>
