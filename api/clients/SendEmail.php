<?php
// Почта пользователя
$email = $_GET['email'];

// Получаем текст из формы
$headerText = $_POST['header'];
$mainText = $_POST['main'];
$footerText = $_POST['footer'];

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/phpmailer/phpmailer/src/Exception.php';
require '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../../vendor/phpmailer/phpmailer/src/SMTP.php';

$mail = new PHPMailer(true);

$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';

$mail->isSMTP();
$mail->Host = 'smtp.mail.ru';
$mail->SMTPAuth = true;
$mail->Username = 'dima.haunov@mail.ru';
$mail->Password = 'ikW5x1urvtS6bnm7afNp';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = 465;

// Добавляем изображения с абсолютными путями
$mail->addEmbeddedImage($_SERVER['DOCUMENT_ROOT'] . '/CRM/img/piska.jpg', 'background', 'background.jpg');
$mail->addEmbeddedImage($_SERVER['DOCUMENT_ROOT'] . '/CRM/img/jopa.png', 'logo', 'logo.png');

// Почта отправителя
$mail->setFrom('dima.haunov@mail.ru', 'Topoi Pidoras+Siske');
// Почта получателя
$mail->addAddress('matviei.maksimov@bk.ru', 'Igor Igigow');

// Сначала формируем HTML
$html = '<!DOCTYPE html><html><head><meta charset="utf-8">
<!--[if gte mso 9]>
<xml>
  <o:OfficeDocumentSettings>
    <o:AllowPNG/>
    <o:PixelsPerInch>96</o:PixelsPerInch>
  </o:OfficeDocumentSettings>
</xml>
<![endif]--></head><body>';

// Добавляем стили
$html .= '<style>
    body { 
        margin: 0;
        padding: 20px;
        min-height: 100vh;
        font-family: Arial, sans-serif;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        background-color: #f5f5f5;
    }
    .background-container {
        background-image: url(cid:background);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        padding: 40px 20px;
        width: 800px;
        margin: 20px auto;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .content { 
        max-width: 600px;
        width: 100%;
        margin: 0 auto;
        padding: 30px;
        line-height: 1.6;
        background-color: rgba(255, 255, 255, 0.95);
        border-radius: 5px;
    }
    .header {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 25px;
        text-align: center;
        color: #333;
    }
    .main-text {
        color: #333;
        font-size: 14px;
        line-height: 1.5;
    }
    .main-text p {
        margin-bottom: 15px;
        text-align: left;
    }
    .footer {
        margin-top: 30px;
        color: #666;
        font-size: 13px;
        text-align: right;
        line-height: 1.6;
    }
    .footer p {
        margin: 0;
        padding: 2px 0;
        text-align: right;
        white-space: pre-line;
    }
    .slogan {
        text-align: center;
        font-size: 24px;
        font-style: italic;
        margin-top: 40px;
        color: #333;
    }
</style>';

// Формируем тело письма - заменяем div на table для лучшей совместимости
$html .= '<table width="800" cellpadding="0" cellspacing="0" border="0" background="cid:background" style="background-image: url(cid:background); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <tr>
        <td align="center" style="padding: 40px 20px;">
            <table width="600" cellpadding="30" cellspacing="0" border="0" style="background-color: rgba(255, 255, 255, 0.95); border-radius: 5px;">
                <tr>
                    <td>';

// Добавляем логотип
$html .= '<div style="text-align: center; margin-bottom: 30px;">
            <img src="cid:logo" alt="Сибирский гостинец" style="max-width: 200px;">
          </div>';

// Добавляем заголовок
$html .= '<div class="header">' . htmlspecialchars($headerText) . '</div>';

// Основной текст
$html .= '<div class="main-text">';
// Нормализуем переносы строк и разделяем на абзацы
$mainText = str_replace(["\r\n", "\r"], "\n", $mainText);
$paragraphs = array_filter(explode("\n\n", trim($mainText)));

foreach ($paragraphs as $paragraph) {
    if (!empty(trim($paragraph))) {
        // Заменяем HTML-теги ссылки на реальную ссылку перед экранированием
        $paragraph = preg_replace('/<a href="#">(.*?)<\/a>/', '$1', $paragraph);
        $paragraph = htmlspecialchars(trim($paragraph));
        // Восстанавливаем ссылку после экранирования
        $paragraph = str_replace('ссылке', '<a href="#" style="color: #0066cc; text-decoration: underline;">ссылке</a>', $paragraph);
        $html .= '<p>' . $paragraph . '</p>';
    }
}
$html .= '</div>';

// Футер
$html .= '<div class="footer">';
$footerLines = explode("\n", nl2br(trim($footerText)));
foreach ($footerLines as $line) {
    if (!empty(trim($line))) {
        // Сохраняем HTML-теги для ссылок
        $line = str_replace(['&lt;', '&gt;'], ['<', '>'], $line);
        // Экранируем только текст, сохраняя HTML-теги
        $line = preg_replace_callback(
            '/<a.*?<\/a>|[^<>]+/',
            function($matches) {
                return strpos($matches[0], '<a') === 0 ? $matches[0] : htmlspecialchars($matches[0]);
            },
            trim($line)
        );
        $html .= '<p>' . $line . '</p>';
    }
}
$html .= '</div>';

// Добавляем слоган
$html .= '<div class="slogan">СИБИРЬ БЛИЖЕ,<br>ЧЕМ ВЫ ДУМАЕТЕ...</div>';

$html .= '</td></tr></table></td></tr></table></body></html>';

// Теперь отправляем письмо
$mail->isHTML(true);
$mail->Subject = 'Сибирский гостинец';
$mail->Body = $html;
$mail->send();

// Можно оставить echo для отладки
// echo $html;