<?php
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      
    $mail->isSMTP();                                            
    $mail->Host       = 'localhost';                            
    $mail->SMTPAuth   = false;                                  
    $mail->Port       = 25;                                     

    //Recipients
    $mail->setFrom('admin@localhost.com', 'Admin User');
    $mail->addAddress('test@localhost.com', 'Test User');

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'Тестовое письмо';
    $mail->Body    = 'Это тестовое письмо с <b>HTML</b> разметкой';
    $mail->AltBody = 'Это тестовое письмо в текстовом формате';

    // Attempt to send
    if($mail->send()) {
        // Создаем txt файл в папке получателя
        $mailDir = 'C:/xampp/MercuryMail/MAIL/test/';
        $fileName = date('Y-m-d_H-i-s') . '_message.txt';
        $content = "От: Admin User <admin@localhost.com>\n";
        $content .= "Кому: Test User <test@localhost.com>\n";
        $content .= "Тема: " . $mail->Subject . "\n\n";
        $content .= "HTML версия:\n" . $mail->Body . "\n\n";
        $content .= "Текстовая версия:\n" . $mail->AltBody;

        file_put_contents($mailDir . $fileName, $content);

        echo '<div style="color: green; padding: 20px;">';
        echo '<h2>✓ Письмо успешно отправлено!</h2>';
        echo '<p>Проверьте файл: ' . $mailDir . $fileName . '</p>';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div style="color: red; padding: 20px;">';
    echo '<h2>✗ Ошибка отправки письма.</h2>';
    echo '<p>Ошибка: ' . $mail->ErrorInfo . '</p>';
    echo '<h3>Отладочная информация:</h3>';
    echo '<pre>';
    echo 'SMTP Host: ' . $mail->Host . "\n";
    echo 'SMTP Port: ' . $mail->Port . "\n";
    echo 'SMTP Secure: ' . ($mail->SMTPSecure ? $mail->SMTPSecure : 'None') . "\n";
    echo 'SMTP Auth: ' . ($mail->SMTPAuth ? 'Yes' : 'No') . "\n";
    echo '</pre>';
    echo '</div>';
} 