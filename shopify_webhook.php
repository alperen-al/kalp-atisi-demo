<?php
// shopify_webhook.php
session_start();
include 'db.php';

// Shopify'dan gelen ham veriyi al
$input = file_get_contents('php://input');
$headers = getallheaders();

// Güvenlik için HMAC kontrolü (Shopify Admin → Settings → Notifications → Webhook oluştururken secret verilir)
$shared_secret = 'SENIN_SHOPIFY_WEBHOOK_SECRETIN';
$hmac_header   = $headers['X-Shopify-Hmac-Sha256'] ?? '';
$calculated_hmac = base64_encode(hash_hmac('sha256', $input, $shared_secret, true));

// Shopify’dan geldi mi?
if (hash_equals($hmac_header, $calculated_hmac)) {
    // Ödeme başarılı! Şimdi verileri kaydedelim
    if (isset($_SESSION['pending_note'])) {
        $data = $_SESSION['pending_note'];

        $note      = trim($data['note'] ?? '');
        $email     = trim($data['email'] ?? '');
        $refCode   = trim($data['refCode'] ?? '');
        $datetime  = trim($data['date'] ?? '');
        $latitude  = trim($data['latitude'] ?? null);
        $longitude = trim($data['longitude'] ?? null);
        $user_id   = $_SESSION['user_id'] ?? null;

        if ($note && $email && $datetime && $user_id) {
            $price = 0.75;
            $pinColor = 'kirmizi';

            $stmt = $conn->prepare("
              INSERT INTO purchases 
                (user_id, purchase_date, price, status, pin_color, latitude, longitude, ref_code_used)
              VALUES (?, NOW(), ?, 'bekliyor', ?, ?, ?, ?)
            ");
            $stmt->bind_param("idsdds", $user_id, $price, $pinColor, $latitude, $longitude, $refCode);
            $stmt->execute();
            $purchase_id = $stmt->insert_id;
            $stmt->close();

            $stmt2 = $conn->prepare("
              INSERT INTO messages 
                (purchase_id, message_text, send_date, send_status, email)
              VALUES (?, ?, ?, 'bekliyor', ?)
            ");
            $stmt2->bind_param("isss", $purchase_id, $note, $datetime, $email);
            $stmt2->execute();
            $stmt2->close();

            // Referans kodu varsa arttır
            if (!empty($refCode)) {
              $u = $conn->prepare("
                UPDATE users 
                SET ref_use_count = IFNULL(ref_use_count, 0) + 1 
                WHERE ref_code = ?
              ");
              $u->bind_param("s", $refCode);
              $u->execute();
              $u->close();
            }

            // Temizlik
            unset($_SESSION['pending_note']);
        }
    }
}
