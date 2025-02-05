<?php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /login.php');
        exit();
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUser($db) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
