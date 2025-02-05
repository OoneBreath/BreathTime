<?php
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $config = require_once __DIR__ . '/../config/database.php';
        
        try {
            $this->pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", 
                $config['username'], 
                $config['password']
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Błąd połączenia z bazą danych: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getPdo() {
        return $this->pdo;
    }
    
    // Metody do zarządzania zakupami
    public function createPurchase($email, $chargeId, $amount) {
        $token = $this->generateToken();
        $stmt = $this->pdo->prepare("
            INSERT INTO purchases (email, charge_id, download_token, amount, timestamp, expiry_date)
            VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))
        ");
        $stmt->execute([$email, $chargeId, $token, $amount]);
        return $token;
    }
    
    public function verifyToken($token) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM purchases 
            WHERE download_token = ? 
            AND is_valid = 1 
            AND (expiry_date IS NULL OR expiry_date > NOW())
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
    
    public function logDownload($token, $format) {
        $stmt = $this->pdo->prepare("
            INSERT INTO downloads (token, format, ip_address, user_agent, timestamp)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $token,
            $format,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        // Aktualizacja licznika pobrań
        $this->pdo->prepare("
            UPDATE purchases 
            SET download_count = download_count + 1 
            WHERE download_token = ?
        ")->execute([$token]);
    }
    
    // Metody pomocnicze
    private function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    public function log($type, $message, $data = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO logs (type, message, data, timestamp)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$type, $message, json_encode($data)]);
    }
}
