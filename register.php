<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/Mailer.php';
require_once 'includes/GeoLocation.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $country_code = $_POST['country_code'] ?? '';
        $terms_accepted = isset($_POST['terms_accepted']);
        $marketing_consent = isset($_POST['marketing_consent']);

        // Pobierz IP użytkownika
        $ip_address = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        // Pobierz dane geolokalizacyjne
        try {
            $geo = GeoLocation::getInstance();
            $location_data = $geo->getLocationData($ip_address);
            $country_code = $location_data['country_code'];
            $country_name = $location_data['country_name'];
            $city = $location_data['city'];
            $region = $location_data['region'];
        } catch (Exception $e) {
            // Loguj błąd, ale pozwól na rejestrację
            error_log("Błąd geolokalizacji: " . $e->getMessage());
            $country_code = '';
            $country_name = '';
            $city = '';
            $region = '';
        }

        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            throw new Exception('Wypełnij wszystkie wymagane pola');
        }

        // Sprawdź akceptację regulaminu
        if (!$terms_accepted) {
            throw new Exception('Musisz zaakceptować regulamin, aby się zarejestrować');
        }

        if ($password !== $confirm_password) {
            throw new Exception('Hasła nie są identyczne');
        }

        if (strlen($password) < 8) {
            throw new Exception('Hasło musi mieć co najmniej 8 znaków');
        }

        // Walidacja emaila
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Nieprawidłowy format adresu email');
        }

        $db = Database::getInstance();
        
        // Sprawdź czy email już istnieje
        $existing_user = $db->query(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        )->fetch();

        if ($existing_user) {
            throw new Exception('Ten email jest już zajęty');
        }

        // Hash hasła
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Generuj token weryfikacyjny
        $verification_token = bin2hex(random_bytes(32));
        
        // Zapisz użytkownika
        $result = $db->query(
            "INSERT INTO users (name, email, password, verification_token, status, terms_accepted, marketing_consent, phone, 
                              country_code, ip_address, country_name, city, region) 
             VALUES (?, ?, ?, ?, 'unverified', ?, ?, ?, ?, ?, ?, ?, ?)",
            [$name, $email, $hashed_password, $verification_token, $terms_accepted, $marketing_consent, $phone, 
             $country_code, $ip_address, $country_name, $city, $region]
        );

        if ($result) {
            // Wyślij email weryfikacyjny
            $mailer = new Mailer();
            try {
                if ($mailer->sendVerificationEmail($email, $name, $verification_token)) {
                    // Wyślij powiadomienie do admina
                    $mailer->sendAdminNewUserNotification($email, $name);
                    $success = 'Konto zostało utworzone. Sprawdź swoją skrzynkę email, aby je aktywować.';
                } else {
                    // Jeśli wysyłka się nie powiedzie, usuń utworzone konto
                    $db->query("DELETE FROM users WHERE email = ?", [$email]);
                    throw new Exception('Nie udało się wysłać emaila weryfikacyjnego. Spróbuj ponownie później.');
                }
            } catch (Exception $e) {
                // Jeśli wystąpi błąd, usuń utworzone konto
                $db->query("DELETE FROM users WHERE email = ?", [$email]);
                throw new Exception('Wystąpił błąd podczas wysyłania emaila weryfikacyjnego: ' . $e->getMessage());
            }
        } else {
            throw new Exception('Wystąpił błąd podczas rejestracji. Spróbuj ponownie później.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Błąd rejestracji: " . $e->getMessage());
    }
}

$page_title = 'Rejestracja - BreathTime';
ob_start(); ?>

<div class="content-box" style="max-width: 500px; margin: 2rem auto;">
    <h1 class="text-center mb-3">Rejestracja</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="needs-validation" novalidate>
        <div class="form-group mb-3">
            <label for="name">Imię i nazwisko *</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>

        <div class="form-group mb-3">
            <label for="email">Email *</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>

        <div class="form-group mb-3">
            <label for="phone">Numer telefonu (opcjonalnie)</label>
            <input type="tel" class="form-control" id="phone" name="phone">
            <input type="hidden" id="country_code" name="country_code">
            <small class="form-text text-muted">Pomoże nam to w lepszej komunikacji i analizie zainteresowania w różnych regionach</small>
        </div>

        <div class="form-group mb-3">
            <label for="password">Hasło *</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <small class="form-text text-muted">Minimum 8 znaków</small>
        </div>

        <div class="form-group mb-3">
            <label for="confirm_password">Powtórz hasło *</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>

        <div class="form-group mb-2">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" 
                       class="custom-control-input" 
                       id="terms_accepted" 
                       name="terms_accepted" 
                       required>
                <label class="custom-control-label small" for="terms_accepted">
                    <span>Akceptuję <a href="view-terms.php" class="text-primary" target="_blank">regulamin</a> 
                    i <a href="view-privacy.php" class="text-primary" target="_blank">politykę prywatności</a></span>
                </label>
            </div>
        </div>

        <div class="form-group mb-3">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" 
                       class="custom-control-input" 
                       id="marketing_consent" 
                       name="marketing_consent">
                <label class="custom-control-label small" for="marketing_consent">
                    <span>Wyrażam zgodę na otrzymywanie informacji marketingowych drogą elektroniczną</span>
                </label>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-block">
                Zarejestruj się
            </button>
        </div>
        
        <div class="text-center mt-4">
            <p class="mb-2">Masz już konto?</p>
            <a href="login.php" class="btn btn-primary">Zaloguj się</a>
        </div>
    </form>
</div>

<!-- Dodaj bibliotekę intl-tel-input -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var input = document.querySelector("#phone");
    var countryCodeInput = document.querySelector("#country_code");
    
    var iti = window.intlTelInput(input, {
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        preferredCountries: ['pl', 'gb', 'de'],
        separateDialCode: true,
        initialCountry: "auto",
        geoIpLookup: function(callback) {
            // Najpierw spróbuj użyć IP użytkownika
            fetch('https://ipapi.co/json')
                .then(response => response.json())
                .then(data => {
                    callback(data.country_code);
                    // Zapisz kod kraju do ukrytego pola
                    countryCodeInput.value = data.country_code.toUpperCase();
                })
                .catch(() => {
                    // Jeśli nie uda się pobrać kraju, ustaw domyślnie Polskę
                    callback('pl');
                    countryCodeInput.value = 'PL';
                });
        },
        customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
            // Dodaj przykładowy numer dla danego kraju
            return "np. " + selectedCountryPlaceholder;
        }
    });

    // Aktualizuj ukryte pole z kodem kraju przy zmianie
    input.addEventListener('countrychange', function() {
        var countryData = iti.getSelectedCountryData();
        countryCodeInput.value = countryData.iso2.toUpperCase();
    });

    // Walidacja formularza
    document.querySelector('form').addEventListener('submit', function(e) {
        if (input.value.trim()) {
            if (!iti.isValidNumber()) {
                e.preventDefault();
                alert('Proszę wprowadzić poprawny numer telefonu dla wybranego kraju');
            }
        }
    });
});
</script>

<style>
.iti {
    width: 100%;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
}

/* Style dla intl-tel-input */
.iti__country-list {
    background-color: #1f2937;
    border-color: #374151;
    font-size: 0.8rem;
    max-height: 150px;
}

.iti__country {
    color: #e5e7eb;
    padding: 4px 8px;
}

.iti__country:hover {
    background-color: #374151;
}

.iti__selected-flag {
    background-color: #1f2937 !important;
}

.iti__selected-flag:hover {
    background-color: #374151 !important;
}

/* Style dla linków */
.custom-control-label {
    font-size: 0.8rem;
    line-height: 1.2;
}

.custom-control-label a {
    color: #60a5fa;
    text-decoration: none;
}

.custom-control-label a:hover {
    color: #93c5fd;
    text-decoration: underline;
}

/* Style dla checkboxów */
.custom-control {
    padding-left: 1.75rem;
    margin-bottom: 0.25rem;
    position: relative;
    min-height: 1.5rem;
}

.custom-control-input {
    position: absolute;
    left: 0;
    top: 0.25rem;
    z-index: 1;
}

.custom-control-label {
    position: relative;
    margin-bottom: 0;
    vertical-align: top;
    padding-top: 0.25rem;
}

.custom-control-label::before,
.custom-control-label::after {
    position: absolute;
    top: 0.25rem;
    left: -1.75rem;
    display: block;
    width: 1rem;
    height: 1rem;
    content: "";
}

.custom-control-input:checked ~ .custom-control-label::before {
    border-color: #60a5fa;
    background-color: #60a5fa;
}

/* Mniejsza czcionka dla tekstu zgód */
.custom-control-label.small {
    font-size: 0.85rem;
    line-height: 1.4;
    display: block;
    padding-top: 0;
}

/* Popraw wyrównanie tekstu w zgodach */
.custom-control-label.small span {
    display: block;
    margin-top: -0.25rem;
}

/* Dodatkowe style dla wyrównania */
.form-label {
    margin-bottom: 0.25rem;
    display: block;
    font-size: 0.9rem;
}

.form-group {
    position: relative;
    margin-bottom: 0.75rem;
}

.custom-control {
    padding-left: 1.75rem;
    margin-bottom: 0.5rem;
    position: relative;
    min-height: 1.5rem;
}

.btn-block {
    width: 100%;
    padding: 0.5rem 1rem;
    font-size: 1rem;
}

/* Większe odstępy między sekcjami */
.form-group:last-of-type {
    margin-bottom: 1.5rem;
}

.d-grid {
    margin-bottom: 2rem;
}
</style>

<?php
$content = ob_get_clean();
require 'includes/tech_layout.php';
?>
