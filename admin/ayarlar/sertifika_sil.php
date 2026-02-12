<?php
session_start();
include '../../db.php';

// Admin girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Sadece admin kullanıcı erişebilir
$stmt = $conn->prepare("SELECT account_type FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($type);
$stmt->fetch();
$stmt->close();

if ($type !== 'admin') {
    echo "Bu sayfaya sadece admin erişebilir.";
    exit;
}

// Sertifika ID al
$cert_id = $_GET['cert_id'] ?? null;

if ($cert_id) {
    // Önce dosya adını al
    $stmt = $conn->prepare("SELECT cert_file FROM certificates WHERE cert_id = ?");
    $stmt->bind_param("i", $cert_id);
    $stmt->execute();
    $stmt->bind_result($cert_file);
    if ($stmt->fetch()) {
        $stmt->close();

        // Dosya yolunu belirle ve sil
        $file_path = "../../certificates/" . $cert_file;
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Veritabanından kaydı sil
        $del = $conn->prepare("DELETE FROM certificates WHERE cert_id = ?");
        $del->bind_param("i", $cert_id);
        $del->execute();
        $del->close();
    } else {
        $stmt->close();
    }
}

// Geri yönlendir
header("Location: ../sertifikalar.php");
exit;
?>
