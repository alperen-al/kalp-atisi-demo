<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

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

if (isset($_GET['purchase_id'])) {
    $purchase_id = intval($_GET['purchase_id']);

    $stmt = $conn->prepare("DELETE FROM purchases WHERE purchase_id = ?");
    $stmt->bind_param("i", $purchase_id);
    if ($stmt->execute()) {
        header("Location: ../satin_alimlar.php?deleted=1");
    } else {
        echo "Silme işlemi başarısız.";
    }
    $stmt->close();
} else {
    echo "Geçersiz işlem.";
}
?>

