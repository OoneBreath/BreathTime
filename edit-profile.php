<?php
require_once 'includes/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';
$db = Database::getInstance();

// Pobierz dane użytkownika
$user = $db->query(
    "SELECT name, email, avatar, phone, country_code, ip_address FROM users WHERE id = ?",
    [$_SESSION['user_id']]
)->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $country_code = $_POST['country_code'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name)) {
        $error = 'Imię i nazwisko jest wymagane';
    } else {
        // Obsługa uploadu avatara
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
                $error = 'Dozwolone są tylko pliki obrazów (JPG, PNG, GIF)';
            } elseif ($_FILES['avatar']['size'] > $max_size) {
                $error = 'Maksymalny rozmiar pliku to 5MB';
            } else {
                $avatar_dir = __DIR__ . '/uploads/avatars';
                if (!file_exists($avatar_dir)) {
                    mkdir($avatar_dir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $avatar_filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
                $avatar_path = $avatar_dir . '/' . $avatar_filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
                    // Usuń stary avatar jeśli istnieje
                    if ($user['avatar'] && file_exists($avatar_dir . '/' . $user['avatar'])) {
                        unlink($avatar_dir . '/' . $user['avatar']);
                    }
                    
                    // Aktualizuj bazę danych
                    $db->query(
                        "UPDATE users SET avatar = ? WHERE id = ?",
                        [$avatar_filename, $_SESSION['user_id']]
                    );
                    $user['avatar'] = $avatar_filename;
                } else {
                    $error = 'Wystąpił błąd podczas zapisywania avatara';
                }
            }
        }

        // Aktualizacja nazwy i telefonu
        $db->query(
            "UPDATE users SET name = ?, phone = ?, country_code = ? WHERE id = ?",
            [$name, $phone, $country_code, $_SESSION['user_id']]
        );
        $user['name'] = $name;
        $user['phone'] = $phone;
        $user['country_code'] = $country_code;
        $success = 'Profil został zaktualizowany.';

        // Zmiana hasła (opcjonalna)
        if (!empty($current_password)) {
            // Sprawdź aktualne hasło
            $current_user = $db->query(
                "SELECT password FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            )->fetch();

            if (!password_verify($current_password, $current_user['password'])) {
                $error = 'Aktualne hasło jest nieprawidłowe';
            } elseif (empty($new_password) || empty($confirm_password)) {
                $error = 'Wypełnij wszystkie pola dotyczące hasła';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Nowe hasła nie są identyczne';
            } elseif (strlen($new_password) < 8) {
                $error = 'Nowe hasło musi mieć co najmniej 8 znaków';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $db->query(
                    "UPDATE users SET password = ? WHERE id = ?",
                    [$hashed_password, $_SESSION['user_id']]
                );
                
                $success = 'Profil i hasło zostały zaktualizowane.';
            }
        }
    }
}

$page_title = 'Edycja profilu - BreathTime';
ob_start(); ?>

<div class="content-box">
    <h1>Edycja profilu</h1>
    
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
    
    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        <div class="avatar-section mb-4">
            <div class="current-avatar">
                <?php if ($user['avatar']): ?>
                    <img src="uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                         alt="Avatar użytkownika"
                         class="avatar-img">
                <?php else: ?>
                    <i class="fas fa-user-circle"></i>
                <?php endif; ?>
            </div>
            <div class="avatar-upload">
                <label for="avatar" class="btn btn-info">
                    <i class="fas fa-camera"></i> Zmień avatar
                </label>
                <input type="file" 
                       id="avatar" 
                       name="avatar" 
                       accept="image/jpeg,image/png,image/gif"
                       class="hidden-file-input">
                <small class="form-text text-muted">
                    Maksymalny rozmiar: 5MB. Dozwolone formaty: JPG, PNG, GIF
                </small>
            </div>
        </div>

        <div class="form-group mb-3">
            <label for="name">Imię i nazwisko *</label>
            <input type="text" 
                   class="form-control" 
                   id="name" 
                   name="name" 
                   value="<?php echo htmlspecialchars($user['name']); ?>" 
                   required>
        </div>

        <div class="form-group mb-3">
            <label for="phone">Numer telefonu (opcjonalnie)</label>
            <input type="tel" 
                   class="form-control" 
                   id="phone" 
                   name="phone" 
                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            <input type="hidden" 
                   id="country_code" 
                   name="country_code" 
                   value="<?php echo htmlspecialchars($user['country_code'] ?? ''); ?>">
            <small class="form-text text-muted">Pomoże nam to w lepszej komunikacji</small>
        </div>

        <div class="form-group mb-3">
            <label>Adres IP</label>
            <input type="text" 
                   class="form-control" 
                   value="<?php echo htmlspecialchars($user['ip_address'] ?? ''); ?>" 
                   disabled>
            <small class="form-text text-muted">Adres IP z ostatniej aktualizacji profilu</small>
        </div>

        <hr>
        
        <h3 class="mb-3">Zmiana hasła (opcjonalnie)</h3>
        <p class="text-muted">Wypełnij poniższe pola tylko jeśli chcesz zmienić hasło</p>
        
        <div class="form-group">
            <label for="current_password">Aktualne hasło</label>
            <input type="password" 
                   id="current_password" 
                   name="current_password" 
                   class="form-control" 
                   autocomplete="current-password">
        </div>
        
        <div class="form-group">
            <label for="new_password">Nowe hasło</label>
            <input type="password" 
                   id="new_password" 
                   name="new_password" 
                   class="form-control" 
                   autocomplete="new-password">
            <small class="form-text text-muted">Minimum 8 znaków</small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Powtórz nowe hasło</label>
            <input type="password" 
                   id="confirm_password" 
                   name="confirm_password" 
                   class="form-control" 
                   autocomplete="new-password">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Zapisz zmiany
            </button>
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
        initialCountry: "<?php echo strtolower($user['country_code'] ?? 'pl'); ?>"
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
.avatar-section {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.current-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--background-light);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid var(--border);
}

.current-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.current-avatar i {
    font-size: 5rem !important;
    color: var(--text-light);
}

.avatar-upload {
    flex-grow: 1;
}

.avatar-upload label {
    font-size: 1.2rem;
    padding: 0.75rem 1.5rem;
}

.avatar-upload .fa-camera {
    font-size: 1.5rem;
    margin-right: 0.75rem;
}

.avatar-upload small {
    font-size: 1rem;
    margin-top: 0.75rem;
    display: block;
}

.hidden-file-input {
    display: none;
}

.form-actions {
    margin-top: 2rem;
    display: flex;
    justify-content: flex-end;
}

hr {
    margin: 2rem 0;
    border: 0;
    border-top: 1px solid var(--border);
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>

<script>
document.getElementById('avatar').onchange = function() {
    if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var currentAvatar = document.querySelector('.current-avatar');
            currentAvatar.innerHTML = '<img src="' + e.target.result + '" alt="Avatar podgląd" class="avatar-img">';
        };
        reader.readAsDataURL(this.files[0]);
    }
};
</script>

<?php
$content = ob_get_clean();
require 'includes/tech_layout.php';
?>
