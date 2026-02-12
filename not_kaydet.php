<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['submit'])) {
    $user_id = $_SESSION['user_id'];
    $message_text = $_POST['message_text'];
    $send_date = $_POST['send_date'];
    $location = $_POST['location'];
    $price = $_POST['price'];

    // Pin rengi: bireysel = kırmızı, şirket = yeşil
    $pin_color = ($_SESSION['account_type'] === 'sirket') ? 'yesil' : 'kirmizi';

    // Şimdilik sabit koordinat (ileride konumdan alınabilir)
    $latitude = 38.4189;
    $longitude = 27.1287;

    // purchases tablosuna ekle
    $stmt = $conn->prepare("INSERT INTO purchases (user_id, purchase_date, price, status, pin_color, latitude, longitude)
                            VALUES (?, NOW(), ?, 'bekliyor', ?, ?, ?)");
    $stmt->bind_param("idsss", $user_id, $price, $pin_color, $latitude, $longitude);
    $stmt->execute();
    $purchase_id = $stmt->insert_id;

    // messages tablosuna ekle
    $stmt2 = $conn->prepare("INSERT INTO messages (purchase_id, message_text, send_date, send_status)
                             VALUES (?, ?, ?, 'bekliyor')");
    $stmt2->bind_param("iss", $purchase_id, $message_text, $send_date);
    $stmt2->execute();

    // Kullanıcıya başarı mesajı göstermek için session yaz
    $_SESSION['mesaj_basarili'] = true;
    header("Location: tr/not.html");
    exit();
}
?>
