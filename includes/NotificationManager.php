<?php

class NotificationManager {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Tworzy nowe powiadomienie
     */
    public function create($userId, $type, $message, $data = [], $link = null) {
        return $this->db->query(
            "INSERT INTO notifications (user_id, type, message, data, link, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$userId, $type, $message, json_encode($data), $link]
        );
    }

    /**
     * Pobiera nieprzeczytane powiadomienia
     */
    public function getUnread($userId, $limit = 10) {
        return $this->db->query(
            "SELECT * FROM notifications 
             WHERE user_id = ? AND read_at IS NULL 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$userId, $limit]
        )->fetchAll();
    }

    /**
     * Pobiera wszystkie powiadomienia
     */
    public function getAll($userId, $limit = 50) {
        return $this->db->query(
            "SELECT * FROM notifications 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$userId, $limit]
        )->fetchAll();
    }

    /**
     * Oznacza powiadomienie jako przeczytane
     */
    public function markAsRead($notificationId, $userId) {
        return $this->db->query(
            "UPDATE notifications 
             SET read_at = NOW() 
             WHERE id = ? AND user_id = ?",
            [$notificationId, $userId]
        );
    }

    /**
     * Oznacza wszystkie powiadomienia jako przeczytane
     */
    public function markAllAsRead($userId) {
        return $this->db->query(
            "UPDATE notifications 
             SET read_at = NOW() 
             WHERE user_id = ? AND read_at IS NULL",
            [$userId]
        );
    }

    /**
     * Liczy nieprzeczytane powiadomienia
     */
    public function countUnread($userId) {
        return $this->db->query(
            "SELECT COUNT(*) as count 
             FROM notifications 
             WHERE user_id = ? AND read_at IS NULL",
            [$userId]
        )->fetch()['count'];
    }

    /**
     * Tworzy powiadomienie o prośbie o rolę
     */
    public function createRoleRequest($userId, $requestedRole, $description) {
        $admins = $this->db->query("SELECT id FROM users WHERE is_admin = 1")->fetchAll();
        
        foreach ($admins as $admin) {
            $data = [
                'user_id' => $userId,
                'requested_role' => $requestedRole,
                'description' => $description
            ];
            
            $this->create(
                $admin['id'],
                'role_request',
                "Nowa prośba o rolę: $requestedRole",
                $data,
                '/admin/users.php'
            );
        }
    }

    /**
     * Tworzy powiadomienie o akceptacji/odrzuceniu roli
     */
    public function createRoleResponse($userId, $role, $accepted) {
        $status = $accepted ? 'zaakceptowana' : 'odrzucona';
        $this->create(
            $userId,
            'role_response',
            "Twoja prośba o rolę '$role' została $status",
            ['role' => $role, 'accepted' => $accepted]
        );
    }
}
