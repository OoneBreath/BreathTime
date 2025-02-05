<?php
require_once 'includes/config.php';
require_once 'includes/mail.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Musisz być zalogowany']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $description = $_POST['description'] ?? '';
    
    $validRoles = [
        'volunteer' => 'Wolontariusz',
        'educator' => 'Edukator',
        'monitor' => 'Monitor',
        'foundation' => 'Fundacja',
        'tech_partner' => 'Partner Technologiczny',
        'edu_institution' => 'Instytucja Edukacyjna',
        'expert' => 'Ekspert',
        'strategic_partner' => 'Partner Strategiczny',
        'ambassador' => 'Ambasador'
    ];
    
    if (isset($validRoles[$role]) && !empty($description)) {
        try {
            $db = Database::getInstance();
            
            // Sprawdź czy użytkownik już nie ma tej roli
            $stmt = $db->query(
                "SELECT role, role_verified, email, name FROM users WHERE id = ?", 
                [$_SESSION['user_id']]
            );
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Jeśli użytkownik nie ma roli lub ma rolę 'user' lub czeka na weryfikację tej samej roli
            if (!$user['role'] || $user['role'] === 'user' || 
                ($user['role'] === $role && !$user['role_verified'])) {
                
                // Zapisz opis i datę aplikacji
                $db->query(
                    "UPDATE users SET 
                        requested_role = ?,
                        role_verified = FALSE,
                        role_application_date = NOW(),
                        role_description = ?
                    WHERE id = ?",
                    [$role, $description, $_SESSION['user_id']]
                );
                
                // Wyślij email do admina
                $adminSubject = "Nowa prośba o rolę - BreathTime";
                $adminMessage = "
                    <p>Użytkownik <strong>{$user['name']}</strong> ({$user['email']}) prosi o przyznanie roli:</p>
                    <div class='highlight'>
                        <strong>{$validRoles[$role]}</strong>
                        <p style='margin-top: 10px;'>{$description}</p>
                    </div>
                    <p>Możesz zatwierdzić lub odrzucić tę prośbę w panelu administratora.</p>";
                
                sendEmail(ADMIN_EMAIL, $adminSubject, $adminMessage);
                
                // Wyślij potwierdzenie do użytkownika
                $userSubject = "Potwierdzenie złożenia prośby o rolę - BreathTime";
                $userMessage = "
                    <p>Witaj <strong>{$user['name']}</strong>,</p>
                    <div class='highlight'>
                        <p>Dziękujemy za złożenie prośby o rolę <strong>{$validRoles[$role]}</strong> w społeczności BreathTime.</p>
                        <p>Twoja prośba została przyjęta i zostanie rozpatrzona przez naszych administratorów.</p>
                    </div>
                    <p>Powiadomimy Cię mailowo, gdy Twoja rola zostanie zatwierdzona lub odrzucona.</p>
                    <p>Pozdrawiamy,<br>Zespół BreathTime</p>";
                
                sendEmail($user['email'], $userSubject, $userMessage);
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => "Dziękujemy za złożenie prośby o rolę {$validRoles[$role]}. " .
                                "Twoje zgłoszenie zostało przyjęte i zostanie rozpatrzone przez naszych administratorów. " .
                                "Powiadomimy Cię mailowo o decyzji."
                ]);
                exit();
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Masz już przypisaną inną rolę. Skontaktuj się z administratorem.']);
            }
        } catch (Exception $e) {
            error_log("Role registration error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Wystąpił błąd podczas rejestracji roli. Spróbuj ponownie później.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Nieprawidłowa rola lub brak opisu.']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Nieprawidłowa metoda HTTP']);
exit();
