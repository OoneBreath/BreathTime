<?php
require_once '../includes/config.php';
require_once '../includes/NotificationManager.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$notifications = NotificationManager::getInstance();
$count = $notifications->countUnread($_SESSION['user_id']);

echo json_encode(['count' => $count]);
