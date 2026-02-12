<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Admin kontrolü
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

// Silme işlemi
if (isset($_GET['withdrawal_id'])) {
    $id = intval($_GET['withdrawal_id']);

    $stmt = $conn->prepare("DELETE FROM withdrawals WHERE withdrawal_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: ../cekim_talepleri.php?success=1");
    } else {
        echo "Silme işlemi başarısız: " . $conn->error;
    }
    $stmt->close();
} else {
    echo "Geçersiz işlem.";
}
?>
