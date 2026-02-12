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

// Talep bilgilerini çek
if (!isset($_GET['withdrawal_id'])) {
    echo "Geçersiz ID.";
    exit;
}
$id = intval($_GET['withdrawal_id']);
$stmt = $conn->prepare("SELECT amount, iban, status FROM withdrawals WHERE withdrawal_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($amount, $iban, $status);
if (!$stmt->fetch()) {
    echo "Kayıt bulunamadı.";
    exit;
}
$stmt->close();

// Güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_amount = $_POST['amount'];
    $new_iban = $_POST['iban'];
    $new_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE withdrawals SET amount=?, iban=?, status=? WHERE withdrawal_id=?");
    $stmt->bind_param("dssi", $new_amount, $new_iban, $new_status, $id);
    if ($stmt->execute()) {
        header("Location: ../cekim_talepleri.php?updated=1");
        exit;
    } else {
        echo "Güncelleme başarısız: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Çekim Talebi Güncelle</title>
    <link rel="stylesheet" href="../../tr/profil.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f9fafb; padding: 40px; }
        .form-container {
            background: white; padding: 30px;
            max-width: 500px; margin: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 10px;
        }
        h2 { text-align: center; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, select {
            width: 100%; padding: 10px; margin-top: 5px;
            border: 1px solid #ccc; border-radius: 5px;
        }
        button {
            margin-top: 20px; padding: 10px 20px;
            background: #1e3a5f; color: white;
            border: none; border-radius: 5px; cursor: pointer;
        }
        button:hover {
            background: #2c5282;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Çekim Talebi Güncelle</h2>
        <form method="POST">
            <label>Tutar ($):</label>
            <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars($amount) ?>" required>

            <label>IBAN:</label>
            <input type="text" name="iban" value="<?= htmlspecialchars($iban) ?>" required>

            <label>Durum:</label>
            <select name="status" required>
                <option value="bekliyor" <?= $status === 'bekliyor' ? 'selected' : '' ?>>Bekliyor</option>
                <option value="onaylandi" <?= $status === 'onaylandi' ? 'selected' : '' ?>>Onaylandı</option>
                <option value="reddedildi" <?= $status === 'reddedildi' ? 'selected' : '' ?>>Reddedildi</option>
            </select>

            <button type="submit">Güncelle</button>
        </form>
    </div>
</body>
</html>
