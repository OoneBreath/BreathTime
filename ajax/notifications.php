<?php
require_once '../includes/config.php';
require_once '../includes/NotificationManager.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['notifications' => []]);
    exit;
}

$notifications = NotificationManager::getInstance();
$list = $notifications->getUnread($_SESSION['user_id'], 10);

// Formatuj daty
foreach ($list as &$notification) {
    $notification['created_at'] = date('d.m.Y H:i', strtotime($notification['created_at']));
    if ($notification['data']) {
        $notification['data'] = json_decode($notification['data'], true);
    }
}

echo json_encode(['notifications' => $list]);
