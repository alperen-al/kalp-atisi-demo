<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
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

// ID kontrolü
if (!isset($_GET['id'])) {
    echo "Mesaj ID bulunamadı.";
    exit;
}

$id = intval($_GET['id']);

// Güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newText = trim($_POST['message_text']);
    $newStatus = trim($_POST['send_status']);

    $stmt = $conn->prepare("UPDATE messages SET message_text = ?, send_status = ? WHERE message_id = ?");
    $stmt->bind_param("ssi", $newText, $newStatus, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../mesajlar.php");
    exit;
}

// Mevcut mesajı getir
$stmt = $conn->prepare("SELECT message_text, send_status FROM messages WHERE message_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($text, $status);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Mesaj Güncelle</title>
    <style>
        body {
            font-family: Arial;
            background: #eef2f7;
            padding: 40px;
        }
        .form-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            width: 400px;
            margin: auto;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 18px;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Mesajı Güncelle</h2>
    <form method="POST">
        <label>Mesaj Metni:</label>
        <textarea name="message_text" rows="4"><?= htmlspecialchars($text) ?></textarea>

        <label>Durum:</label>
        <select name="send_status">
            <option value="bekliyor" <?= $status === 'bekliyor' ? 'selected' : '' ?>>Bekliyor</option>
            <option value="gönderildi" <?= $status === 'gönderildi' ? 'selected' : '' ?>>Gönderildi</option>
        </select>

        <button type="submit">Kaydet</button>
    </form>
</div>
</body>
</html>
