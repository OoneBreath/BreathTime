<?php
session_start();
require_once 'includes/config.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

// Sprawdź czy użytkownik jest administratorem
$user = $db->query(
    "SELECT is_admin FROM users WHERE id = ?",
    [$_SESSION['user_id']]
)->fetch();

if (!$user || !$user['is_admin']) {
    header('Location: petitions.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['petition_id'])) {
    // Sprawdź czy petycja należy do użytkownika i jest szkicem
    $petition = $db->query(
        "SELECT * FROM petitions WHERE id = ? AND created_by = ? AND status = 'draft'",
        [$_POST['petition_id'], $_SESSION['user_id']]
    )->fetch();
    
    if ($petition) {
        // Aktualizuj status na aktywny
        $db->query(
            "UPDATE petitions SET status = 'active' WHERE id = ?",
            [$petition['id']]
        );
        
        // Dodaj powiadomienie
        $db->query(
            "INSERT INTO notifications (user_id, type, message, action_url) VALUES (?, 'petition_status', ?, ?)",
            [
                $_SESSION['user_id'],
                "Twoja petycja \"{$petition['title']}\" została opublikowana.",
                "view-petition.php?id={$petition['id']}"
            ]
        );
        
        header('Location: my-petitions.php');
        exit();
    }
}

header('Location: my-petitions.php');
exit();
