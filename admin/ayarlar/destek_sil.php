<?php
session_start();
include '../../db.php';

// Sadece admin girişi yapılmışsa devam et
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Admin değilse çık
$stmt = $conn->prepare("SELECT account_type FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($type);
$stmt->fetch();
$stmt->close();

if ($type !== 'admin') {
    echo "Bu işlemi yalnızca admin gerçekleştirebilir.";
    exit;
}

// Silinecek destek ID’si kontrol edilir
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Geçersiz destek ID";
    exit;
}

$destek_id = intval($_GET['id']);

// Silme işlemi
$stmt = $conn->prepare("DELETE FROM destek WHERE id = ?");
$stmt->bind_param("i", $destek_id);
if ($stmt->execute()) {
    $stmt->close();
    header("Location: ../destek.php");
    exit;
} else {
    echo "Silme işlemi başarısız: " . $conn->error;
    $stmt->close();
}
?>
