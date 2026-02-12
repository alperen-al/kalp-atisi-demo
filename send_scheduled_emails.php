<?php
// Türkiye saatine göre zaman ayarı
date_default_timezone_set('Europe/Istanbul');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer dosyaları
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

include 'db.php';

// Şu anki zaman
$now = date("Y-m-d H:i:s");
echo "⏱️ Şu anki zaman: $now<br><br>";

// Gönderilmeyi bekleyen mesajları al
$query = "
SELECT 
  m.message_id, m.message_text, m.send_date, m.email, m.send_status,
  u.username AS sender_name
FROM messages m
JOIN purchases p ON m.purchase_id = p.purchase_id
JOIN users u ON p.user_id = u.user_id
WHERE m.send_status = 'bekliyor' AND m.send_date <= ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $now);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "❌ Gönderilecek mesaj bulunamadı.<br>";
} else {
    echo "🔍 {$result->num_rows} mesaj bulundu. İşleniyor...<br><br>";
}

while ($row = $result->fetch_assoc()) {
    echo "➡️ İşleniyor: Mesaj ID {$row['message_id']} → {$row['email']}<br>";

    $mail = new PHPMailer(true);

    try {
        // SMTP Ayarları
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dunyaninkalbiatiyor@gmail.com';
        $mail->Password   = 'cvmhuedbiflzsfgo'; // Gmail uygulama şifren
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Gönderen ve alıcı
        $mail->setFrom('dunyaninkalbiatiyor@gmail.com', $row['sender_name']);
        $mail->addAddress(urldecode($row['email'])); // %40 → @ çözümü

        // Mail içeriği
        $mail->isHTML(true);
        $mail->Subject = "Kalp Mesajı ❤️";
        $mail->Body    = "
            <h2>📨 Kalp Mesajı</h2>
            <p><strong>Gönderen:</strong> {$row['sender_name']}</p>
            <hr>
            <p>{$row['message_text']}</p>
        ";

        $mail->send();

        // Gönderildiyse veritabanında işaretle
        $update = $conn->prepare("UPDATE messages SET send_status = 'gönderildi' WHERE message_id = ?");
        $update->bind_param("i", $row['message_id']);
        $update->execute();
        $update->close();

        echo "✅ Gönderildi: Mesaj ID {$row['message_id']}<br><br>";

    } catch (Exception $e) {
        echo "❌ HATA: Mesaj ID {$row['message_id']} gönderilemedi → {$mail->ErrorInfo}<br><br>";
    }
}

$stmt->close();
?>
