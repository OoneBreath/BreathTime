<?php

class GeoLocation {
    private static $instance = null;

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getLocationData($ip) {
        // Jeśli IP to localhost, użyj publicznego API do testów
        if ($ip == '127.0.0.1' || $ip == '::1' || $ip == 'localhost') {
            $ip = '8.8.8.8'; // Do testów lokalnych
        }

        $url = "http://ip-api.com/json/{$ip}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception('Nie udało się pobrać danych geolokalizacyjnych: ' . $curl_error);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['status']) || $data['status'] !== 'success') {
            error_log('API Response: ' . print_r($data, true));
            throw new Exception('Błąd API geolokalizacji: ' . ($data['message'] ?? 'Nieznany błąd'));
        }
        
        return [
            'country_code' => $data['countryCode'] ?? null,
            'country_name' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
            'region' => $data['regionName'] ?? null
        ];
    }
}
