<?php

function sendEmail($to, $subject, $message) {
    // Ustaw nagłówki
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: BreathTime <" . ADMIN_EMAIL . ">" . "\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    
    // Przygotuj szablon HTML
    $htmlMessage = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f5f5f5;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                padding: 40px 20px;
                text-align: center;
            }
            .logo {
                width: 180px;
                height: auto;
                margin: 0 auto 15px;
                display: block;
                filter: brightness(0) invert(1);
            }
            .brand-name {
                color: #ffffff;
                font-size: 28px;
                font-weight: 600;
                margin: 0;
                text-shadow: 0 2px 4px rgba(0,0,0,0.2);
                letter-spacing: 1px;
            }
            .content {
                padding: 40px 30px;
                background: #ffffff;
                color: #333333;
                font-size: 16px;
                line-height: 1.6;
            }
            .footer {
                text-align: center;
                padding: 30px 20px;
                background: #f9f9f9;
                font-size: 12px;
                color: #666;
                border-top: 1px solid #eeeeee;
            }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                color: #ffffff;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
                font-weight: 500;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
            }
            .button:hover {
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                transform: translateY(-1px);
            }
            .social-links {
                margin: 20px 0;
                color: #999;
            }
            .social-links span {
                margin: 0 10px;
            }
            p {
                margin: 0 0 15px 0;
            }
            .highlight {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #1e3c72;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            strong {
                color: #1e3c72;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <img src="https://breathtime.info/images/breathtime-logo.svg" alt="BreathTime" class="logo">
                <h1 class="brand-name">BreathTime</h1>
            </div>
            <div class="content">
                ' . nl2br($message) . '
            </div>
            <div class="footer">
                <p> ' . date('Y') . ' BreathTime. Wszelkie prawa zastrzeżone.</p>
                <p style="color: #999; font-size: 11px; margin-top: 10px;">Ta wiadomość została wysłana automatycznie. Prosimy nie odpowiadać na ten adres email.</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Wyślij email
    return mail($to, $subject, $htmlMessage, $headers);
}
