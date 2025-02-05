<?php

class SecurityCheck {
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
     * Sprawdza czy nazwa użytkownika wygląda jak bot
     */
    public function isSpamUsername($username) {
        // Typowe wzorce botów
        $spamPatterns = [
            '/[0-9]{6,}/', // Długie ciągi cyfr
            '/[a-zA-Z0-9]{20,}/', // Bardzo długie nazwy
            '/[0-9]{3,}[a-zA-Z]{3,}[0-9]{3,}/', // Mieszanka cyfr i liter w specyficznym wzorcu
            '/(.)\1{4,}/', // Powtarzające się znaki (np. 'aaaaa')
            '/[A-Z]{5,}/', // Długie ciągi wielkich liter
            '/bot|spam|test|admin|root/i', // Zakazane słowa
            '/[^a-zA-Z0-9\s\-_]/', // Dziwne znaki
        ];

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $username)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Sprawdza czy IP nie jest zbanowane i nie przekracza limitu rejestracji
     */
    public function checkIP($ip) {
        // Sprawdź czy IP jest zbanowane
        $banned = $this->db->query(
            "SELECT 1 FROM banned_ips WHERE ip = ? AND ban_expires > NOW()",
            [$ip]
        )->fetch();

        if ($banned) {
            throw new Exception('To IP jest tymczasowo zablokowane. Spróbuj później.');
        }

        // Sprawdź liczbę rejestracji z tego IP w ostatniej godzinie
        $recentRegistrations = $this->db->query(
            "SELECT COUNT(*) as count FROM users WHERE registration_ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$ip]
        )->fetch()['count'];

        if ($recentRegistrations >= 3) {
            // Zbanuj IP na godzinę
            $this->db->query(
                "INSERT INTO banned_ips (ip, ban_expires) VALUES (?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
                 ON DUPLICATE KEY UPDATE ban_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR)",
                [$ip]
            );
            throw new Exception('Zbyt wiele prób rejestracji. Spróbuj ponownie za godzinę.');
        }

        return true;
    }

    /**
     * Sprawdza czy email nie jest z podejrzanej domeny
     */
    public function isSpamEmail($email) {
        $spamDomains = [
            'tempmail.com', 'throwaway.com', 'mailinator.com', 
            'guerrillamail.com', 'yopmail.com', 'tempmail.net'
        ];

        $domain = substr(strrchr($email, "@"), 1);
        return in_array(strtolower($domain), $spamDomains);
    }

    /**
     * Sprawdza wszystkie warunki bezpieczeństwa
     */
    public function validateRegistration($username, $email, $ip) {
        if ($this->isSpamUsername($username)) {
            throw new Exception('Ta nazwa użytkownika nie jest dozwolona.');
        }

        if ($this->isSpamEmail($email)) {
            throw new Exception('Ten adres email nie jest dozwolony.');
        }

        $this->checkIP($ip);
        return true;
    }
}
