<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Używamy PHP mail() zamiast SMTP
            $this->mailer->isMail();
            
            // Ustawienia domyślne
            $this->mailer->setFrom('info@breathtime.info', 'BreathTime');
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->isHTML(true);
            
            // Debugowanie
            $this->mailer->SMTPDebug = 2;
            $this->mailer->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug [$level]: $str");
            };
            
        } catch (Exception $e) {
            error_log("Błąd konfiguracji mailer: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function sendVerificationEmail($email, $name, $token) {
        try {
            $subject = 'Potwierdź swój email - BreathTime';
            $message = "
                <p>Witaj <strong>{$name}</strong>!</p>
                <div class='highlight'>
                    <p>Dziękujemy za dołączenie do społeczności BreathTime. Aby aktywować swoje konto, kliknij poniższy przycisk:</p>
                </div>
                <a href='https://breathtime.info/verify.php?token={$token}' class='button'>
                    Potwierdź email
                </a>
                <p style='margin-top: 20px;'><strong>Link jest ważny przez 24 godziny.</strong></p>
                <p>Jeśli nie zakładałeś/aś konta w BreathTime, zignoruj tę wiadomość.</p>";
            
            return sendEmail($email, $subject, $message);
        } catch (Exception $e) {
            error_log("Błąd wysyłania maila weryfikacyjnego: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function sendWelcomeEmail($email, $name) {
        try {
            $subject = 'Witaj w BreathTime!';
            $message = "
                <p>Witaj <strong>{$name}</strong>!</p>
                <div class='highlight'>
                    <p>Cieszymy się, że dołączyłeś/aś do naszej społeczności. Twoje konto zostało pomyślnie aktywowane.</p>
                </div>
                <p>Co możesz teraz zrobić?</p>
                <ul>
                    <li>Uzupełnij swój profil</li>
                    <li>Dołącz do naszych akcji</li>
                    <li>Zapoznaj się z materiałami edukacyjnymi</li>
                    <li>Weź udział w dyskusjach</li>
                </ul>
                <a href='https://breathtime.info/dashboard.php' class='button'>
                    Przejdź do panelu
                </a>";
            
            return sendEmail($email, $subject, $message);
        } catch (Exception $e) {
            error_log("Błąd wysyłania maila powitalnego: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function sendPasswordResetEmail($email, $name, $token) {
        try {
            $subject = 'Reset hasła - BreathTime';
            $message = "
                <p>Witaj <strong>{$name}</strong>!</p>
                <div class='highlight'>
                    <p>Otrzymaliśmy prośbę o reset hasła do Twojego konta w BreathTime.</p>
                    <p>Aby zresetować hasło, kliknij poniższy przycisk:</p>
                </div>
                <a href='https://breathtime.info/reset-password.php?token={$token}' class='button'>
                    Resetuj hasło
                </a>
                <p style='margin-top: 20px;'><strong>Link jest ważny przez 1 godzinę.</strong></p>
                <p>Jeśli nie prosiłeś/aś o reset hasła, zignoruj tę wiadomość.</p>";
            
            return sendEmail($email, $subject, $message);
        } catch (Exception $e) {
            error_log("Błąd wysyłania maila resetującego hasło: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function sendAdminNewUserNotification($userEmail, $userName) {
        try {
            $subject = 'Nowy użytkownik w BreathTime';
            $message = "
                <p>Witaj!</p>
                <div class='highlight'>
                    <p>Nowy użytkownik zarejestrował się w systemie BreathTime:</p>
                    <ul>
                        <li>Nazwa: <strong>{$userName}</strong></li>
                        <li>Email: <strong>{$userEmail}</strong></li>
                    </ul>
                </div>
                <p>Użytkownik musi jeszcze potwierdzić swój adres email.</p>";
            
            return sendEmail(ADMIN_EMAIL, $subject, $message);
        } catch (Exception $e) {
            error_log("Błąd wysyłania powiadomienia do admina: " . $e->getMessage());
            // Nie rzucamy wyjątku, bo to nie jest krytyczne
            return false;
        }
    }
    
    public function sendAdminNewSignatureNotification($petitionTitle, $userName, $petitionId) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress(ADMIN_EMAIL);
            
            $this->mailer->Subject = 'Nowy podpis pod petycją - BreathTime';
            
            $this->mailer->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff;'>
                    <div style='text-align: center; padding: 20px 0; background-color: #111827;'>
                        <img src='https://breathtime.info/images/breathtime-logo-negative-256px.png' 
                             alt='BreathTime' 
                             style='width: 200px; height: auto; margin: 0 auto;'>
                    </div>
                    <div style='padding: 20px;'>
                        <h2 style='color: #333;'>Nowy podpis pod petycją</h2>
                        <p>Użytkownik podpisał petycję w systemie BreathTime:</p>
                        <ul style='margin: 20px 0;'>
                            <li>Użytkownik: {$userName}</li>
                            <li>Petycja: {$petitionTitle}</li>
                        </ul>
                        <p style='margin: 20px 0; text-align: center;'>
                            <a href='https://breathtime.info/admin/petitions.php?id={$petitionId}' 
                               style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                                Zobacz szczegóły
                            </a>
                        </p>
                        <hr style='margin: 20px 0;'>
                        <p style='color: #666; font-size: 12px; text-align: center;'>
                            BreathTime - Powiadomienie systemowe
                        </p>
                    </div>
                </div>
            ";
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('Błąd wysyłania powiadomienia do admina o nowym podpisie: ' . $e->getMessage());
            return false;
        }
    }
}
?>
