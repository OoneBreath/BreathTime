<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/GeoLocation.php';

try {
    $geo = GeoLocation::getInstance();
    
    // Pobierz IP użytkownika
    $ip_address = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    echo "<pre>";
    echo "Twoje IP: " . $ip_address . "\n\n";
    
    $location_data = $geo->getLocationData($ip_address);
    
    echo "Dane geolokalizacyjne:\n";
    echo "Kraj (kod): " . $location_data['country_code'] . "\n";
    echo "Kraj: " . $location_data['country_name'] . "\n";
    echo "Miasto: " . $location_data['city'] . "\n";
    echo "Region: " . $location_data['region'] . "\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage();
    
    if (isset($data)) {
        echo "<pre>Debug:\n";
        print_r($data);
        echo "</pre>";
    }
}
